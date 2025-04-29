<?php
// app/Filament/Resources/PesananResource/Pages/CreatePesanan.php

namespace App\Filament\Resources\PesananResource\Pages;

use App\Filament\Resources\PesananResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log; // Bisa dihapus jika tidak dipakai lagi

class CreatePesanan extends CreateRecord
{
    protected static string $resource = PesananResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Log::info('Data Pesanan SEBELUM Create:', $data); // Hapus dd() atau Log::info jika sudah tidak perlu

        // === TAMBAHKAN LOGIKA KALKULASI TOTAL HARGA DI SINI ===
        $items = $data['items'] ?? [];
        $total = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $jumlah = $item['jumlah'] ?? 0;
                // Ambil harga dari data repeater, bukan query lagi
                $harga = $item['harga_saat_pesan'] ?? 0;
                $total += $jumlah * $harga;
            }
        }
        // Langsung modifikasi data yang akan disimpan
        $data['total_harga'] = $total;
        // === AKHIR LOGIKA KALKULASI ===

        // Kembalikan data yang sudah dimodifikasi (dengan total harga terisi)
        return $data;
    }

    // Method handleRecordCreation (jika Anda menambahkannya sebelumnya) bisa tetap ada
    // protected function handleRecordCreation(array $data): Model { ... }
}