<?php
// File: app/Filament/Widgets/IkanPopulerChart.php

namespace App\Filament\Widgets;

use App\Models\Ikan; // Import Ikan
use App\Models\Pesanan; // Import Pesanan
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB; // Import DB facade

class IkanPopulerChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Ikan Paling Banyak Dipesan (Status Selesai)';

    // Urutan widget di dashboard, setelah stats dan chart bulanan
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // Query untuk menghitung total jumlah per ikan dari pesanan yang 'Selesai'
        $data = DB::table('ikan_pesanan') // Mulai dari tabel pivot
            ->join('pesanan', 'ikan_pesanan.pesanan_id', '=', 'pesanan.id') // Join ke pesanan
            ->join('ikan', 'ikan_pesanan.ikan_id', '=', 'ikan.id') // Join ke ikan
            ->select('ikan.nama_ikan', DB::raw('SUM(ikan_pesanan.jumlah) as total_jumlah')) // Pilih nama ikan dan jumlah total
            ->where('pesanan.status', '=', 'Selesai') // Hanya dari pesanan selesai
            ->groupBy('ikan.id', 'ikan.nama_ikan') // Kelompokkan per ikan
            ->orderByDesc('total_jumlah') // Urutkan dari jumlah terbanyak
            ->limit(5) // Ambil 5 teratas
            ->get();

        // Siapkan data untuk chart
        $labels = $data->pluck('nama_ikan')->toArray(); // Ambil semua nama ikan sebagai label
        $values = $data->pluck('total_jumlah')->toArray(); // Ambil semua total jumlah sebagai data

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Terjual',
                    'data' => $values,
                    // Anda bisa definisikan warna per bar jika mau
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.6)', // Biru
                        'rgba(255, 99, 132, 0.6)',  // Merah
                        'rgba(75, 192, 192, 0.6)', // Hijau kebiruan
                        'rgba(255, 205, 86, 0.6)', // Kuning
                        'rgba(153, 102, 255, 0.6)',// Ungu
                    ],
                    'borderColor' => [ // Warna border (opsional)
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    'borderWidth' => 1 // Lebar border (opsional)
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // Jenis grafik: 'bar'
        return 'bar';
    }
}