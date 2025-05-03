<?php
// File: app/Http/Resources/IkanResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
// use Illuminate\Support\Facades\Storage; // <-- Import Storage
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
            'nama' => $this->nama_ikan,
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'harga' => (int) $this->harga,
            'stok' => (int) $this->stok,
            'status_ketersediaan' => $this->status_ketersediaan,
            'gambar_utama' => $this->gambar_utama,
            'kategori' => KategoriResource::make($this->whenLoaded('kategori')),
            'dibuat_pada' => $this->created_at,
            'diupdate_pada' => $this->updated_at,
        ];
    }
}