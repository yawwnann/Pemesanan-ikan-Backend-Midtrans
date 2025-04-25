<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriIkan extends Model
{
    use HasFactory;

    protected $table = 'kategori_ikan'; // Nama tabel eksplisit

    protected $fillable = [
        'nama_kategori',
        'slug',
        'deskripsi',
    ];

    // Relasi: Satu kategori punya banyak ikan
    public function ikan(): HasMany
    {
        return $this->hasMany(Ikan::class, 'kategori_id');
    }
}