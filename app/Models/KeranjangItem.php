<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeranjangItem extends Model
{
    use HasFactory;

    protected $table = 'keranjang_items'; // Eksplisit jika nama tabel berbeda

    protected $fillable = [
        'user_id',
        'ikan_id',
        'quantity',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ikan(): BelongsTo
    {
        // Pastikan nama model Ikan Anda benar (misal: App\Models\Ikan)
        return $this->belongsTo(Ikan::class);
    }
}