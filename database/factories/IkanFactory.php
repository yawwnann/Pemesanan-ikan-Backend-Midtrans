<?php
// database/factories/IkanFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ikan; // Import model
use App\Models\KategoriIkan; // Import KategoriIkan
use Illuminate\Support\Str; // Import Str

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ikan>
 */
class IkanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ambil ID kategori secara acak (pastikan KategoriIkanSeeder sudah jalan)
        $kategoriId = KategoriIkan::inRandomOrder()->first()?->id;

        // Jika tidak ada kategori, kembalikan array kosong atau default
        if (!$kategoriId) {
            return []; // Atau berikan nilai default yg valid
        }

        // Daftar contoh nama ikan (bisa diperbanyak)
        $namaIkanList = ['Cupang Halfmoon', 'Guppy Cobra', 'Neon Tetra', 'Discus Blue Diamond', 'Arwana Silver', 'Lele Sangkuriang', 'Nila Merah', 'Patin', 'Oscar Tiger', 'Louhan Cencu', 'Manfish Platinum', 'Molly Balon', 'Platy Mickey Mouse', 'Corydoras Sterbai', 'Koki Oranda', 'Komet Slayer'];
        $namaIkan = $namaIkanList[array_rand($namaIkanList)] . ' ' . fake()->colorName(); // Tambah warna acak
        $stok = fake()->numberBetween(0, 150); // Stok acak 0-150

        return [
            'kategori_id' => $kategoriId,
            'nama_ikan' => $namaIkan,
            'slug' => Str::slug($namaIkan) . '-' . uniqid(), // Tambah uniqid agar unik
            'deskripsi' => fake()->paragraph(2),
            'harga' => fake()->numberBetween(5000, 350000), // Harga acak
            'stok' => $stok,
            'status_ketersediaan' => $stok > 0 ? 'Tersedia' : 'Habis', // Otomatis berdasarkan stok
            'gambar_utama' => null, // Kosongkan gambar dummy
        ];
    }
}