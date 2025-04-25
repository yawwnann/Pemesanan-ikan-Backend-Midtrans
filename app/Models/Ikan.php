<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ikan extends Model
{
    use HasFactory;

    protected $table = 'ikan'; // Nama tabel eksplisit

    protected $fillable = [
        'kategori_id',
        'nama_ikan',
        'slug',
        'deskripsi',
        'harga',
        'status_ketersediaan',
        'gambar_utama',
    ];

    /**
     * Casting atribut ke tipe data native.
     *
     * @var array
     */
    protected $casts = [
        'harga' => 'integer', // Sesuaikan jika menggunakan DECIMAL
    ];


    // Relasi: Satu ikan milik satu kategori
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriIkan::class, 'kategori_id');
    }
}