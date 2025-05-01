<?php
// File: app/Services/PesananService.php

namespace App\Services; // Pastikan namespace sesuai lokasi folder

use App\Models\Pesanan;
use App\Models\Ikan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PesananService
{
    /**
     * Membuat Pesanan baru, menyimpan item, dan mengurangi stok.
     * Dijalankan dalam transaksi database.
     *
     * @param array $data Data dari form atau API (sudah divalidasi)
     * @return Pesanan Model Pesanan yang baru dibuat
     * @throws Exception Jika stok tidak cukup atau error lain
     */
    public function createOrder(array $data): Pesanan
    {
        Log::info('PesananService::createOrder data received:', $data);

        // Gunakan transaction untuk memastikan semua operasi berhasil atau dibatalkan bersamaan
        return DB::transaction(function () use ($data) {

            $itemsData = $data['items'] ?? [];
            $pesananData = Arr::except($data, ['items']); // Ambil data utama Pesanan
            $pivotData = [];
            $total = 0;

            // 1. Validasi stok & siapkan data pivot sebelum membuat pesanan utama
            if (empty($itemsData)) {
                throw new Exception("Pesanan harus memiliki minimal 1 item ikan.");
            }

            foreach ($itemsData as $item) {
                $jumlah = intval($item['jumlah'] ?? 0);
                $ikanId = $item['ikan_id'] ?? null;

                if (!$ikanId || $jumlah <= 0) {
                    throw new Exception("Data item tidak valid: " . json_encode($item));
                }

                $ikan = Ikan::find($ikanId);
                if (!$ikan) {
                    throw new Exception("Ikan dengan ID {$ikanId} tidak ditemukan.");
                }

                // Cek stok di sini sebelum lanjut
                if ($ikan->stok < $jumlah) {
                    throw new Exception("Stok untuk ikan '{$ikan->nama_ikan}' tidak mencukupi (Stok: {$ikan->stok}, Dipesan: {$jumlah}).");
                }

                $harga = $ikan->harga; // Ambil harga terbaru
                $total += $jumlah * $harga;
                $pivotData[$ikanId] = [
                    'jumlah' => $jumlah,
                    'harga_saat_pesan' => $harga,
                ];
            }

            // Isi total harga & status default jika belum ada dari $data
            $pesananData['total_harga'] = $pesananData['total_harga'] ?? $total; // Gunakan total terhitung jika tidak ada
            $pesananData['status'] = $pesananData['status'] ?? 'Baru';
            $pesananData['tanggal_pesan'] = $pesananData['tanggal_pesan'] ?? now()->toDateString();

            // 2. Buat record Pesanan utama
            $pesanan = Pesanan::create($pesananData);
            Log::info("Pesanan record created in service, ID: {$pesanan->id}");

            // 3. Attach items ke tabel pivot
            if (!empty($pivotData)) {
                Log::info("Attaching items via service for Pesanan ID: {$pesanan->id}", $pivotData);
                $pesanan->items()->attach($pivotData);
                Log::info("Items attached via service for Pesanan ID: {$pesanan->id}");

                // 4. Kurangi Stok (Setelah attach berhasil)
                foreach ($pivotData as $ikanId => $pivot) {
                    $ikanInstance = Ikan::find($ikanId); // Ambil ulang instance
                    if ($ikanInstance) {
                        $affectedRows = $ikanInstance->decrement('stok', $pivot['jumlah']);
                        Log::info("Stock decremented via service for Ikan ID: {$ikanId} by {$pivot['jumlah']}. Affected: {$affectedRows}");
                    }
                }
            }

            return $pesanan; // Kembalikan pesanan yang berhasil
        }); // Akhir transaction
    }

    /**
     * Mengupdate Pesanan beserta item.
     * TODO: Implementasi penyesuaian stok yang lebih kompleks saat update/delete item.
     */
    public function updateOrder(Pesanan $pesanan, array $data): Pesanan
    {
        Log::info("PesananService::updateOrder initiated for ID {$pesanan->id}", $data);

        return DB::transaction(function () use ($pesanan, $data) {
            $itemsData = $data['items'] ?? [];
            $pesananData = Arr::except($data, ['items']);
            $pivotData = [];
            $total = 0;

            // Hitung ulang total & siapkan data pivot dari data baru
            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $jumlah = intval($item['jumlah'] ?? 0);
                    $harga = $item['harga_saat_pesan'] ?? 0; // Ambil harga dari form saat edit
                    $ikanId = $item['ikan_id'] ?? null;

                    if ($ikanId && $jumlah > 0) {
                        $total += $jumlah * $harga;
                        $pivotData[$ikanId] = [
                            'jumlah' => $jumlah,
                            'harga_saat_pesan' => $harga,
                        ];
                    }
                }
            }
            $pesananData['total_harga'] = $total;

            // TODO: Logika Penyesuaian Stok Saat Update (kompleks)
            // 1. Dapatkan item lama SEBELUM di-sync.
            // 2. Bandingkan item lama dan baru ($pivotData).
            // 3. Hitung selisih jumlah untuk setiap ikan.
            // 4. Kembalikan stok untuk item yg dihapus/dikurangi.
            // 5. Kurangi stok untuk item yg ditambah/ditambah jumlahnya.
            // Ini perlu dilakukan sebelum atau sesudah sync dengan hati-hati.
            // Untuk sekarang, kita lewati logika penyesuaian stok saat update.

            // 1. Update Pesanan Utama
            $pesanan->update($pesananData);
            Log::info("PesananService: Pesanan record updated, ID: {$pesanan->id}");

            // 2. Sync Items (Pivot Table)
            Log::info("PesananService: Syncing items for Pesanan ID: {$pesanan->id}");
            $pesanan->items()->sync($pivotData); // Sync cocok untuk update
            Log::info("PesananService: Items synced for Pesanan ID: {$pesanan->id}");

            return $pesanan; // Kembalikan pesanan yang sudah diupdate
        }); // Akhir transaction
    }

    // Anda bisa tambahkan method deleteOrder(Pesanan $pesanan) di sini
    // yang juga menangani pengembalian stok jika diperlukan.
}