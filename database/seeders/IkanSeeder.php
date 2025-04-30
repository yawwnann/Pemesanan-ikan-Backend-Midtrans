<?php
// database/seeders/IkanSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ikan; // Import model
use App\Models\KategoriIkan; // Import Kategori

class IkanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cek apakah ada kategori
        if (KategoriIkan::count() == 0) {
            $this->command->warn('Tidak ada Kategori Ikan. Menjalankan KategoriIkanSeeder terlebih dahulu...');
            $this->call(KategoriIkanSeeder::class); // Panggil seeder kategori
        }

        // Buat 30 data ikan dummy menggunakan factory
        $this->command->info('Membuat 30 data ikan dummy...');
        Ikan::factory(30)->create();
        $this->command->info('Seeder Ikan selesai.');
    }
}