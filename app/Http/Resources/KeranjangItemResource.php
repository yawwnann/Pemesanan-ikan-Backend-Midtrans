<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\IkanResource; // Import IkanResource jika Anda punya

class KeranjangItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            // Pastikan relasi 'ikan' di-load di controller sebelum resource ini digunakan
            // Ini akan menggunakan IkanResource untuk format data ikan (disarankan)
            'ikan' => new IkanResource($this->whenLoaded('ikan')),
            // Jika tidak pakai IkanResource:
            // 'ikan' => $this->whenLoaded('ikan', function () {
            //     return [
            //         'id' => $this->ikan->id,
            //         'nama_ikan' => $this->ikan->nama_ikan, // Sesuaikan field name
            //         'slug' => $this->ikan->slug,
            //         'harga' => $this->ikan->harga,
            //         'gambar_utama' => $this->ikan->gambar_utama,
            //         'stok' => $this->ikan->stok,
            //         'status_ketersediaan' => $this->ikan->status_ketersediaan,
            //         // Jangan sertakan kategori di sini kecuali di-load juga
            //     ];
            // }),
            'created_at' => $this->created_at?->toISOString(), // Format ke ISO string
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}