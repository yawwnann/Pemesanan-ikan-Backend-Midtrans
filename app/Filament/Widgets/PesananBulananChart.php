<?php
// File: app/Filament/Widgets/PesananBulananChart.php

namespace App\Filament\Widgets; // Pastikan namespace benar

use App\Models\Pesanan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PesananBulananChart extends ChartWidget
{
    protected static ?string $heading = 'Total Pemasukan per Bulan (Status Selesai)';

    // Atur urutan tampil widget di dashboard (angka lebih kecil tampil lebih atas)
    // Sesuaikan angkanya jika perlu, misal jika PesananStatsOverview punya $sort = 1
    protected static ?int $sort = 2;

    // Opsi filter rentang waktu
    protected function getFilters(): ?array
    {
        return [
            '6_bulan' => '6 Bulan Terakhir',
            '12_bulan' => '12 Bulan Terakhir',
            'tahun_ini' => 'Tahun Ini',
        ];
    }

    protected function getData(): array
    {
        // Ambil filter aktif dari query string, default ke '12_bulan'
        $filter = $this->filter ?? '12_bulan';

        // Tentukan tanggal mulai berdasarkan filter
        $startDate = match ($filter) {
            '6_bulan' => Carbon::now()->subMonths(5)->startOfMonth(),
            'tahun_ini' => Carbon::now()->startOfYear(),
            default => Carbon::now()->subMonths(11)->startOfMonth(), // default '12_bulan'
        };
        $endDate = Carbon::now()->endOfMonth();

        // Query data pesanan selesai per bulan
        $data = Pesanan::query()
            ->select(
                DB::raw('YEAR(tanggal_pesan) as year'),
                DB::raw('MONTH(tanggal_pesan) as month'),
                DB::raw('SUM(total_harga) as aggregate')
            )
            ->where('status', 'Selesai') // Hanya hitung yg selesai
            ->whereBetween('tanggal_pesan', [$startDate, $endDate])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Siapkan label bulan dan nilai data untuk chart
        $labels = [];
        $values = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $monthYear = $currentDate->format('M Y'); // Format: Apr 2025
            $labels[] = $monthYear;

            $monthlyData = $data->first(function ($item) use ($currentDate) {
                return $item->year == $currentDate->year && $item->month == $currentDate->month;
            });

            $values[] = $monthlyData ? $monthlyData->aggregate : 0;

            $currentDate->addMonth(); // Pindah ke bulan berikutnya
        }

        // Kembalikan data dalam format yang dikenali ChartWidget
        return [
            'datasets' => [
                [
                    'label' => 'Total Pemasukan (Rp)',
                    'data' => $values,
                    'borderColor' => '#36A2EB', // Warna biru
                    'backgroundColor' => '#9BD0F5', // Warna area (opsional)
                    'tension' => 0.1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // Jenis grafik: 'line', 'bar', 'pie', 'doughnut', 'radar', 'polarArea'
        return 'line';
    }
}