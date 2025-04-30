<?php
// File: app/Filament/Resources/PesananResource/Pages/CreatePesanan.php

namespace App\Filament\Resources\PesananResource\Pages;

use App\Filament\Resources\PesananResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePesanan extends CreateRecord
{
    protected static string $resource = PesananResource::class;

    // Method ini tetap ada untuk hitung total harga sebelum create
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $items = $data['items'] ?? [];
        $total = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $jumlah = $item['jumlah'] ?? 0;
                $harga = $item['harga_saat_pesan'] ?? 0;
                if (!empty($jumlah) && !empty($harga)) {
                    $total += $jumlah * $harga;
                }
            }
        }
        $data['total_harga'] = $total;
        return $data;
    }

    // Method ini dimodifikasi cara menyimpan relasi items nya
    protected function handleRecordCreation(array $data): Model
    {
        // Pakai transaction tetap bagus
        return DB::transaction(function () use ($data) {
            $itemsData = $data['items'] ?? [];
            $pesananData = Arr::except($data, ['items']); // Data untuk tabel pesanan

            // Pastikan total harga sudah ada dari mutateFormDataBeforeCreate
            if (!isset($pesananData['total_harga'])) {
                // Hitung lagi jika perlu (sebagai fallback)
                $total = 0;
                if (is_array($itemsData)) { /*... kalkulasi total ...*/
                }
                $pesananData['total_harga'] = $total;
            }

            // 1. Buat record Pesanan utama
            Log::info('Membuat record Pesanan utama...', $pesananData);
            $record = static::getModel()::create($pesananData);
            Log::info("Record Pesanan utama dibuat, ID: {$record->id}");

            // 2. Loop melalui itemsData dan attach satu per satu
            Log::info('Memulai proses attach item...');
            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $ikanId = $item['ikan_id'] ?? null;
                    $jumlah = $item['jumlah'] ?? 0;
                    $harga = $item['harga_saat_pesan'] ?? 0;

                    if ($ikanId && $jumlah > 0) {
                        Log::info("Attaching Ikan ID: {$ikanId} with Jumlah: {$jumlah}, Harga: {$harga}");
                        try {
                            // Method attach: parameter pertama ID relasi, parameter kedua array data pivot
                            $record->items()->attach($ikanId, [
                                'jumlah' => $jumlah,
                                'harga_saat_pesan' => $harga
                            ]);
                            Log::info("Berhasil attach Ikan ID: {$ikanId}");
                        } catch (\Exception $e) {
                            Log::error("Gagal attach Ikan ID: {$ikanId} - " . $e->getMessage());
                            // throw $e; // Lempar ulang error jika ingin transaksi gagal total
                        }
                    } else {
                        Log::warning('Skipping item, Ikan ID atau Jumlah tidak valid:', $item);
                    }
                }
            }
            Log::info("Proses attach item selesai untuk Pesanan ID: {$record->id}");

            // 3. Kembalikan record Pesanan
            return $record;
        }); // Akhir transaction
    }
}