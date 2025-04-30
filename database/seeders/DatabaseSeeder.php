<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Panggil seeder lain jika ada (misal User, Kategori, Ikan)
        // $this->call([
        //     UserSeeder::class, // Pastikan ada user admin
        //     KategoriIkanSeeder::class, // Pastikan ada kategori
        //     IkanSeeder::class, // Pastikan ada data ikan dengan stok
        // ]);
        // Panggil seeder Kategori dulu
        $this->call(KategoriIkanSeeder::class); // <-- Panggil Seeder Kategori

        // Panggil seeder Ikan setelah Kategori ada
        $this->call(IkanSeeder::class);         // <-- Panggil Seeder Ikan

        // Panggil seeder pesanan
        $this->call(PesananSeeder::class); // <-- TAMBAHKAN INI
    }
}