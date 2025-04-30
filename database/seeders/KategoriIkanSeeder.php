<?php
// database/seeders/KategoriIkanSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KategoriIkan; // Import model
use Illuminate\Support\Str; // Import Str

class KategoriIkanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategori = [
            'Ikan Hias Air Tawar',
            'Ikan Hias Air Laut',
            'Ikan Predator',
            'Ikan Konsumsi Air Tawar',
            'Ikan Konsumsi Air Laut',
            'Aquascape Fauna',
        ];

        foreach ($kategori as $nama) {
            KategoriIkan::updateOrCreate(
                ['nama_kategori' => $nama], // Cari berdasarkan nama
                ['slug' => Str::slug($nama)] // Buat/Update slug jika perlu
            );
        }

        $this->command->info('Seeder Kategori Ikan selesai.');
    }
}