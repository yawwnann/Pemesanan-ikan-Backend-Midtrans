<?php
// File: app/Http/Resources/PesananResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PesananResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Load relasi items jika belum ada (penting!)
        $this->loadMissing('items');

        return [
            'id' => $this->id,
            'nama_pelanggan' => $this->nama_pelanggan,
            'nomor_whatsapp' => $this->nomor_whatsapp,
            'alamat_pengiriman' => $this->alamat_pengiriman,
            'total_harga' => (int) $this->total_harga, // Pastikan ini sudah dihitung & disimpan
            'tanggal_pesan' => $this->tanggal_pesan ? $this->tanggal_pesan->format('Y-m-d') : null,
            'status' => $this->status,
            'catatan' => $this->catatan,
            'dibuat_pada' => $this->created_at,
            // Sertakan detail item ikan yang dipesan
            'items' => $this->whenLoaded('items', function () {
                // Format setiap item
                return $this->items->map(function ($ikan) {
                    return [
                        'ikan_id' => $ikan->id,
                        'nama_ikan' => $ikan->nama_ikan,
                        'jumlah' => $ikan->pivot->jumlah,
                        'harga_saat_pesan' => (int) $ikan->pivot->harga_saat_pesan,
                    ];
                });
            }),
        ];
    }
}