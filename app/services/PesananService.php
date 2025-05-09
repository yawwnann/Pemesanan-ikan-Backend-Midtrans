<?php

namespace App\Services;

use App\Models\Pesanan;
use App\Models\Ikan;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Snap;
use Exception;

class PesananService
{
    public function createOrder(array $validatedData, User $user): Pesanan
    {
        $keranjangItems = $user->keranjangItems()->with('ikan')->get();

        if ($keranjangItems->isEmpty()) {
            throw new Exception('Keranjang kosong, tidak dapat membuat pesanan.');
        }

        $totalHarga = 0;
        $pivotData = [];
        $listOfIkanToUpdateStock = [];

        foreach ($keranjangItems as $item) {
            if (!$item->ikan) {
                Log::error("Data ikan tidak ditemukan untuk keranjang item ID: {$item->id} milik User ID: {$user->id}");
                throw new Exception("Terjadi masalah saat mengambil detail item di keranjang Anda.");
            }
            $ikan = $item->ikan;
            $jumlah = (int) $item->quantity;

            if ($jumlah <= 0)
                continue;

            $ikanFresh = Ikan::find($ikan->id);
            if (!$ikanFresh || $ikanFresh->stok < $jumlah) {
                throw new Exception("Stok untuk ikan '{$ikan->nama_ikan}' tidak mencukupi (Stok: {$ikanFresh->stok}, Dipesan: {$jumlah}).");
            }

            $harga = (int) $ikanFresh->harga;
            $totalHarga += $jumlah * $harga;
            $pivotData[$ikan->id] = ['jumlah' => $jumlah, 'harga_saat_pesan' => $harga];
            $listOfIkanToUpdateStock[] = ['id' => $ikan->id, 'jumlah' => $jumlah];
        }

        if (empty($pivotData)) {
            throw new Exception("Tidak ada item valid di keranjang untuk dipesan.");
        }

        $midtransOrderId = 'ORDER-' . Str::uuid()->toString();

        return DB::transaction(function () use ($validatedData, $user, $keranjangItems, $totalHarga, $midtransOrderId, $pivotData, $listOfIkanToUpdateStock) {

            $pesananData = [
                'user_id' => $user->id,
                'nama_pelanggan' => $validatedData['nama_pelanggan'],
                'nomor_whatsapp' => $validatedData['nomor_whatsapp'],
                'alamat_pengiriman' => $validatedData['alamat_pengiriman'],
                'catatan' => $validatedData['catatan'] ?? null,
                'total_harga' => $totalHarga,
                'tanggal_pesan' => now()->toDateString(),
                'status' => 'Baru',
                'status_pembayaran' => 'pending',
                'metode_pembayaran' => null,
                'midtrans_order_id' => $midtransOrderId,
                'midtrans_transaction_id' => null,
            ];

            $pesanan = Pesanan::create($pesananData);
            $pesanan->items()->attach($pivotData);

            foreach ($listOfIkanToUpdateStock as $ikanData) {
                $ikanToUpdate = Ikan::where('id', $ikanData['id'])->lockForUpdate()->first();
                if (!$ikanToUpdate || $ikanToUpdate->stok < $ikanData['jumlah']) {
                    throw new Exception("Stok untuk ikan ID {$ikanData['id']} tidak mencukupi saat update.");
                }
                $ikanToUpdate->decrement('stok', $ikanData['jumlah']);
            }

            $user->keranjangItems()->delete();

            Log::info("Pesanan baru berhasil dibuat [ID: {$pesanan->id}, Midtrans Order ID: {$midtransOrderId}] oleh User ID: {$user->id}");

            return $pesanan;
        });
    }

    public function getMidtransSnapToken(Pesanan $pesanan): string
    {
        $pesanan->loadMissing(['user', 'items']);

        $midtransOrderId = $pesanan->midtrans_order_id;
        if (!$midtransOrderId) {
            Log::error("Midtrans Order ID tidak ditemukan pada Pesanan ID: {$pesanan->id} saat generate token.");
            throw new Exception("Referensi pembayaran untuk pesanan ini tidak ditemukan.");
        }

        $item_details = [];
        $calculated_gross_amount = 0;

        if ($pesanan->items && $pesanan->items->isNotEmpty()) {
            foreach ($pesanan->items as $item) {
                if (!$item->pivot || !isset($item->pivot->harga_saat_pesan) || !isset($item->pivot->jumlah)) {
                    Log::warning("Data pivot (jumlah/harga) tidak lengkap untuk item ikan ID: {$item->id} di pesanan {$pesanan->id}");
                    continue;
                }
                $itemPrice = (int) $item->pivot->harga_saat_pesan;
                $itemQuantity = (int) $item->pivot->jumlah;

                if ($itemQuantity <= 0)
                    continue;

                $itemName = $item->nama_ikan ?? null;

                if (empty(trim($itemName ?? ''))) {
                    Log::warning("Nama ikan kosong untuk Ikan ID {$item->id} di Pesanan ID {$pesanan->id}. Menggunakan fallback.");
                    $itemName = "Produk #" . $item->id;
                }

                $item_details[] = [
                    'id' => (string) $item->id,
                    'price' => $itemPrice,
                    'quantity' => $itemQuantity,
                    'name' => Str::limit($itemName, 50, '...'),
                ];
                $calculated_gross_amount += $itemPrice * $itemQuantity;
            }
        } else {
            Log::warning("Tidak ada item ditemukan (relasi items) untuk Pesanan ID {$pesanan->id} saat generate Snap Token.");
            throw new Exception("Tidak ada detail item yang valid untuk pesanan ini.");
        }

        if (empty($item_details)) {
            throw new Exception("Tidak bisa membuat token pembayaran tanpa detail item yang valid.");
        }

        if ($calculated_gross_amount !== (int) $pesanan->total_harga) {
            Log::warning("Gross amount dari item ({$calculated_gross_amount}) berbeda dengan total_harga pesanan ({$pesanan->total_harga}) untuk Midtrans Order ID [{$midtransOrderId}]. Menggunakan hasil kalkulasi item untuk Midtrans.");
        }
        $gross_amount_to_send = $calculated_gross_amount;

        $user = $pesanan->user;
        $customer_details = [
            'first_name' => $pesanan->nama_pelanggan,
            'last_name' => '',
            'email' => $user ? $user->email : ('guest-' . $pesanan->id . '@pasifix.com'),
            'phone' => $pesanan->nomor_whatsapp,
        ];

        $transaction_details = [
            'order_id' => $midtransOrderId,
            'gross_amount' => $gross_amount_to_send,
        ];

        $params = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
        ];

        Log::debug("Midtrans Params Check for Order ID [{$midtransOrderId}]:");
        Log::debug("  Transaction Details (gross_amount): " . $params['transaction_details']['gross_amount']);
        $item_details_sum_check = 0;
        foreach ($params['item_details'] as $idx => $item) {
            $item_total = (int) $item['price'] * (int) $item['quantity'];
            Log::debug("  Item #{$idx}: ID={$item['id']}, Price=" . (int) $item['price'] . ", Qty=" . (int) $item['quantity'] . ", Name='{$item['name']}', ItemTotal={$item_total}");
            $item_details_sum_check += $item_total;
        }
        Log::debug("  Calculated Item Details Sum Check: " . $item_details_sum_check);
        if ($params['transaction_details']['gross_amount'] !== $item_details_sum_check) {
            Log::error("  MISMATCH DETECTED before sending! Gross Amount: {$params['transaction_details']['gross_amount']}, Calculated Sum: {$item_details_sum_check}");
            throw new Exception("Terjadi ketidakcocokan antara total harga dan detail item.");
        } else {
            Log::info("  Gross amount matches calculated sum.");
        }

        try {
            Log::info("Meminta Snap Token untuk Midtrans Order ID [{$midtransOrderId}]");
            $snapToken = Snap::getSnapToken($params);
            Log::info("Snap Token berhasil didapatkan untuk Midtrans Order ID [{$midtransOrderId}]");
            return $snapToken;
        } catch (Exception $e) {
            Log::error("Gagal mendapatkan Snap Token untuk Midtrans Order ID [{$midtransOrderId}]: " . $e->getMessage());
            throw new Exception("Gagal memulai sesi pembayaran: " . $e->getMessage());
        }
    }

    public function updateOrder(Pesanan $pesanan, array $data): Pesanan
    {
        return DB::transaction(function () use ($pesanan, $data) {
            $itemsData = $data['items'] ?? [];
            $pesananData = Arr::except($data, ['items']);
            $pivotData = [];
            $total = 0;

            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $jumlah = intval($item['jumlah'] ?? 0);
                    $harga = $item['harga_saat_pesan'] ?? 0;
                    $ikanId = $item['ikan_id'] ?? null;
                    if ($ikanId && $jumlah > 0) {
                        $total += $jumlah * $harga;
                        $pivotData[$ikanId] = ['jumlah' => $jumlah, 'harga_saat_pesan' => $harga];
                    }
                }
            }
            if (!empty($itemsData)) {
                $pesananData['total_harga'] = $total;
            }
            $pesananData['user_id'] = $data['user_id'] ?? $pesanan->user_id;

            $pesanan->update($pesananData);

            if (!empty($itemsData)) {
                $pesanan->items()->sync($pivotData);
            }

            return $pesanan;
        });
    }
}
