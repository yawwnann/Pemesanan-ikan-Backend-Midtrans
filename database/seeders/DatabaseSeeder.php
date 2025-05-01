<?php
// File: database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// Import semua class Seeder yang akan dipanggil
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder; // Asumsi Anda punya UserSeeder untuk admin
use Database\Seeders\KategoriIkanSeeder;
use Database\Seeders\IkanSeeder;
use Database\Seeders\PesananSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Jalankan semua seeder utama dari sini dengan urutan yang benar.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,          // 1. Buat Roles
            UserSeeder::class,          // 2. Buat User Admin (assign role 'admin') <-- PASTIKAN INI ADA
            KategoriIkanSeeder::class,
            IkanSeeder::class,
            PesananSeeder::class,
        ]);
    }
}