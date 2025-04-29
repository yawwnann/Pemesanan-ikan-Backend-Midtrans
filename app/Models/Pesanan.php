<?php
// File: app/Models/Pesanan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Pastikan ini di-import
use App\Models\Ikan;                                     // Pastikan ini di-import

class Pesanan extends Model
{
    use HasFactory;

    protected $table = 'pesanan';

    protected $fillable = [
        'nama_pelanggan',
        'nomor_whatsapp',
        // 'ikan_id', // Hapus ini
        // 'ikan_dipesan', // Hapus ini juga
        'total_harga',
        'tanggal_pesan',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_pesan' => 'date',
        'total_harga' => 'integer', // atau 'decimal:0'
    ];

    /**
     * Relasi Many-to-Many ke model Ikan melalui tabel pivot 'ikan_pesanan'.
     * Nama method relasi ini HARUS 'items' agar cocok dengan Repeater::make('items')->relationship().
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Ikan::class, 'ikan_pesanan')
            ->withPivot('jumlah', 'harga_saat_pesan')
            ->withTimestamps(); // Jika tabel pivot pakai timestamps
    }
}