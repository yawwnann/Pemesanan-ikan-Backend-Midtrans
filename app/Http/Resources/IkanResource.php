<?php
// File: app/Http/Resources/IkanResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage; // <-- Import Storage

class IkanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Tentukan data apa saja yang ingin ditampilkan di API
        return [
            'id' => $this->id,
            'nama' => $this->nama_ikan, // Ganti nama field jika perlu
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'harga' => (int) $this->harga, // Jadikan integer
            'stok' => (int) $this->stok,
            'status_ketersediaan' => $this->status_ketersediaan,
            // Buat URL lengkap untuk gambar jika ada
            'gambar_url' => $this->gambar_utama ? Storage::url($this->gambar_utama) : null,
            'kategori' => KategoriResource::make($this->whenLoaded('kategori')), // Masukkan data kategori jika di-load
            'dibuat_pada' => $this->created_at,
            'diupdate_pada' => $this->updated_at,
        ];
    }
}