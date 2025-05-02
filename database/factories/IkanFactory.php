<?php
// database/factories/IkanFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Ikan; // Import model
use App\Models\KategoriIkan; // Import KategoriIkan
use Illuminate\Support\Str; // Import Str
use Illuminate\Support\Facades\Http; // Import Laravel HTTP Client
use Illuminate\Support\Facades\Log; // Import Log facade untuk debugging

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
        // ... (kode untuk $kategoriId, $namaIkanList, $namaIkan, $stok sama seperti sebelumnya)
        $kategoriId = KategoriIkan::inRandomOrder()->first()?->id;
        if (!$kategoriId) {
            throw new \Exception('Tidak ada Kategori Ikan di database. Jalankan KategoriIkanSeeder terlebih dahulu.');
        }
        $namaIkanList = ['Cupang Halfmoon', 'Guppy Cobra', 'Neon Tetra', 'Discus Blue Diamond', 'Arwana Silver', 'Lele Sangkuriang', 'Nila Merah', 'Patin', 'Oscar Tiger', 'Louhan Cencu', 'Manfish Platinum', 'Molly Balon', 'Platy Mickey Mouse', 'Corydoras Sterbai', 'Koki Oranda', 'Komet Slayer'];
        $namaIkan = $namaIkanList[array_rand($namaIkanList)] . ' ' . fake()->colorName();
        $stok = fake()->numberBetween(0, 150);

        // --- LOGIKA PENGAMBILAN GAMBAR DARI PIXABAY (DIMODIFIKASI) ---
        $imageUrl = null;
        $apiKey = env('PIXABAY_API_KEY');

        if (!empty($apiKey)) {
            // Siapkan query pencarian (ambil 1-2 kata pertama + tambahkan "fish")
            $queryParts = explode(' ', $namaIkan);
            $baseQuery = $queryParts[0] . (isset($queryParts[1]) ? ' ' . $queryParts[1] : ''); // Ambil 1-2 kata pertama

            // --- PERUBAHAN DI SINI: Tambahkan kata " fish" ---
            $searchQuery = urlencode($baseQuery . ' fish');

            // URL API Pixabay (kategori tetap 'animals' karena mungkin tidak ada 'fish')
            $apiUrl = "https://pixabay.com/api/?key={$apiKey}&q={$searchQuery}&image_type=photo&category=animals&safesearch=true&per_page=3";

            try {
                $response = Http::timeout(10)->get($apiUrl);

                if ($response->successful() && $response->json('totalHits') > 0) {
                    $imageUrl = $response->json('hits.0.webformatURL');
                } else {
                    // Log jika gagal atau tidak ketemu (pesan log disesuaikan)
                    Log::warning("Pixabay API: No relevant fish found for query '{$searchQuery}'. Status: " . $response->status());
                    // Opsional: Anda bisa mencoba pencarian kedua tanpa ' fish' di sini jika mau
                }
            } catch (\Exception $e) {
                Log::error("Pixabay API Error for query {$searchQuery}: " . $e->getMessage());
            }
        } else {
            Log::warning('PIXABAY_API_KEY is not set in .env file.');
        }

        // Fallback jika masih null
        if (is_null($imageUrl)) {
            $imageUrl = 'https://via.placeholder.com/300x200.png?text=Fish+Not+Found'; // Fallback placeholder lebih jelas
        }
        // ----------------------------------------------

        return [
            'kategori_id' => $kategoriId,
            'nama_ikan' => $namaIkan,
            'slug' => Str::slug($namaIkan) . '-' . uniqid(),
            'deskripsi' => fake()->paragraph(2),
            'harga' => fake()->numberBetween(5000, 350000),
            'stok' => $stok,
            'status_ketersediaan' => $stok > 0 ? 'Tersedia' : 'Habis',
            'gambar_utama' => $imageUrl,
        ];
    }
}