<?php
// database/seeders/PesananSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pesanan;
use App\Models\Ikan;
use Illuminate\Support\Facades\Log;

class PesananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $availableIkan = Ikan::where('stok', '>', 0)->get(['id', 'harga', 'stok']);

        if ($availableIkan->isEmpty()) {
            $this->command->warn('Tidak ada Ikan tersedia dengan stok > 0 untuk dibuat pesanan dummy.');
            return;
        }

        $this->command->info('Membuat 25 data pesanan dummy...');

        // === GUNAKAN withoutEvents() DI SINI ===
        Pesanan::withoutEvents(function () use ($availableIkan) {

            Pesanan::factory(25)->create()->each(function ($pesanan) use ($availableIkan) {
                try {
                    $jumlahItem = rand(1, 3);
                    $itemsToAttach = [];
                    $totalHargaPesanan = 0;
                    $sampledIkan = $availableIkan->random(min($jumlahItem, $availableIkan->count()))->unique('id');

                    foreach ($sampledIkan as $ikan) {
                        $maxJumlah = $ikan->stok > 0 ? $ikan->stok : 1;
                        $jumlah = rand(1, min(5, $maxJumlah));
                        $hargaSatuan = $ikan->harga;
                        $totalHargaPesanan += $jumlah * $hargaSatuan;
                        $itemsToAttach[$ikan->id] = [
                            'jumlah' => $jumlah,
                            'harga_saat_pesan' => $hargaSatuan
                        ];
                    }

                    if (!empty($itemsToAttach)) {
                        // Attach items (ini tidak akan memicu event 'created' observer karena ada di dalam withoutEvents)
                        $pesanan->items()->attach($itemsToAttach);
                        // Update total harga
                        $pesanan->total_harga = $totalHargaPesanan;
                        $pesanan->saveQuietly(); // Simpan tanpa memicu event lagi
                    }
                } catch (\Exception $e) {
                    Log::error("Error attaching items to Pesanan ID {$pesanan->id}: " . $e->getMessage());
                    $this->command->error("Error attaching items to Pesanan ID {$pesanan->id}.");
                }
            });

        }); // <-- Akhir dari closure withoutEvents

        $this->command->info('25 data pesanan dummy berhasil dibuat.');
    }
}