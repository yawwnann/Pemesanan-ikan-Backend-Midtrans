<?php
// File: app/Http/Resources/KategoriResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KategoriResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama_kategori,
            'slug' => $this->slug,
            // 'deskripsi' => $this->deskripsi, // Opsional
        ];
    }
}