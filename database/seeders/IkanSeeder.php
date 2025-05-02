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

        // Opsional: Hapus semua data ikan sebelum seeding baru
        // Aktifkan baris ini jika Anda ingin tabel dikosongkan setiap kali db:seed dijalankan
        // Hati-hati: Ini akan menghapus SEMUA data ikan yang ada!
        // Ikan::truncate(); 

        // Buat 30 data ikan dummy menggunakan factory
        $this->command->info('Membuat 30 data ikan dummy...');
        Ikan::factory(30)->create(); // Anda bisa mengganti 30 sesuai kebutuhan
        $this->command->info('Seeder Ikan selesai.');
    }
}