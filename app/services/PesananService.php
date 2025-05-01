<?php
// File: app/Services/PesananService.php

namespace App\Services;

use App\Models\Pesanan;
use App\Models\Ikan;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PesananService
{
    /**
     * Membuat Pesanan baru.
     */
    public function createOrder(array $data, ?User $user = null): Pesanan
    {
        return DB::transaction(function () use ($data, $user) {
            $itemsData = $data['items'] ?? [];
            $pesananData = Arr::except($data, ['items']);
            $pivotData = [];
            $total = 0;
            $listOfIkanToUpdateStock = []; // Tampung ikan & jumlah untuk update stok

            if (empty($itemsData)) {
                throw new Exception("Pesanan harus memiliki minimal 1 item ikan.");
            }

            // 1. Validasi & Siapkan Data
            foreach ($itemsData as $item) {
                $jumlah = intval($item['jumlah'] ?? 0);
                $ikanId = $item['ikan_id'] ?? null;

                if (!$ikanId || $jumlah <= 0)
                    continue; // Lewati item tidak valid

                $ikan = Ikan::find($ikanId);
                if (!$ikan)
                    throw new Exception("Ikan dengan ID {$ikanId} tidak ditemukan.");
                if ($ikan->stok < $jumlah)
                    throw new Exception("Stok untuk ikan '{$ikan->nama_ikan}' tidak mencukupi (Stok: {$ikan->stok}, Dipesan: {$jumlah}).");

                $harga = $ikan->harga;
                $total += $jumlah * $harga;
                $pivotData[$ikanId] = ['jumlah' => $jumlah, 'harga_saat_pesan' => $harga];
                $listOfIkanToUpdateStock[] = ['instance' => $ikan, 'jumlah' => $jumlah]; // Simpan instance ikan
            }

            // Isi data pesanan
            $pesananData['total_harga'] = $pesananData['total_harga'] ?? $total;
            $pesananData['status'] = $pesananData['status'] ?? 'Baru';
            $pesananData['tanggal_pesan'] = $pesananData['tanggal_pesan'] ?? now()->toDateString();
            if ($user) {
                $pesananData['user_id'] = $user->id;
                $pesananData['nama_pelanggan'] = $pesananData['nama_pelanggan'] ?? $user->name;
            } else {
                $pesananData['user_id'] = $data['user_id'] ?? null;
            }

            // 2. Buat Pesanan Utama
            $pesanan = Pesanan::create($pesananData);

            // 3. Attach Items
            if (!empty($pivotData)) {
                $pesanan->items()->attach($pivotData);

                // 4. Kurangi Stok (setelah attach berhasil)
                foreach ($listOfIkanToUpdateStock as $ikanData) {
                    $ikanData['instance']->decrement('stok', $ikanData['jumlah']);
                }
            }

            return $pesanan;
        });
    }

    /**
     * Mengupdate Pesanan.
     * Note: Penyesuaian stok saat update belum detail.
     */
    public function updateOrder(Pesanan $pesanan, array $data): Pesanan
    {
        // TODO: Implementasi logika penyesuaian stok saat update (kompleks)

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
            $pesananData['total_harga'] = $total;
            $pesananData['user_id'] = $data['user_id'] ?? $pesanan->user_id;

            // 1. Update Pesanan Utama
            $pesanan->update($pesananData);

            // 2. Sync Items (Pivot Table)
            $pesanan->items()->sync($pivotData);

            return $pesanan;
        });
    }

    // public function deleteOrder(Pesanan $pesanan): bool { ... }
}