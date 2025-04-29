<?php
// app/Models/Ikan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ikan extends Model
{
    use HasFactory;
    protected $table = 'ikan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kategori_id',         // <-- Pastikan ada
        'nama_ikan',           // <-- Pastikan ada
        'slug',                // <-- Pastikan ada
        'deskripsi',           // <-- Pastikan ada
        'harga',               // <-- Pastikan ada
        'stok',                // <-- Pastikan ada
        'status_ketersediaan', // <-- Pastikan ada
        'gambar_utama',        // <-- Pastikan ada
    ];

    protected $casts = [
        'harga' => 'integer',
        'stok' => 'integer',
    ];

    // Relasi ke Kategori
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriIkan::class, 'kategori_id');
    }

    // Relasi ke Pesanan (jika sudah dibuat)
    public function pesanan(): BelongsToMany
    {
        return $this->belongsToMany(Pesanan::class, 'ikan_pesanan')
            ->withPivot('jumlah', 'harga_saat_pesan')
            ->withTimestamps();
    }
}