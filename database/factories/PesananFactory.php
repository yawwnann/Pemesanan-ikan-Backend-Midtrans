<?php
// database/factories/PesananFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Pesanan; // Import Pesanan
use Carbon\Carbon; // Import Carbon untuk manipulasi tanggal

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pesanan>
 */
class PesananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Daftar status yang mungkin
        $statuses = ['Baru', 'Diproses', 'Dikirim', 'Selesai', 'Batal'];

        // Generate tanggal acak dalam 12 bulan terakhir
        $tanggal = Carbon::now()->subMonths(rand(0, 11))->subDays(rand(0, 28));

        return [
            'nama_pelanggan' => fake()->name(), // Nama palsu
            'nomor_whatsapp' => fake()->numerify('08##########'), // Nomor WA palsu (format Indonesia)
            'alamat_pengiriman' => fake()->address(), // Alamat palsu
            'tanggal_pesan' => $tanggal,
            'status' => $statuses[array_rand($statuses)], // Pilih status acak
            'catatan' => fake()->optional(0.3)->sentence(), // 30% kemungkinan ada catatan
            'total_harga' => 0, // Default 0, akan dihitung ulang nanti
            'created_at' => $tanggal->copy()->addHours(rand(1, 5)), // Waktu dibuat sedikit setelah tanggal pesan
            'updated_at' => $tanggal->copy()->addHours(rand(6, 10)), // Waktu update setelah dibuat
        ];
    }
}