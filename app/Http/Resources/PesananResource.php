<?php

// File: app/Http/Resources/PesananResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log; // Untuk logging jika diperlukan
// use App\Http\Resources\IkanResource; // Opsional

class PesananResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this merujuk pada instance model Pesanan yang sedang diproses
        return [
            'id' => $this->id,
            'nama_pelanggan' => $this->nama_pelanggan,
            'nomor_whatsapp' => $this->nomor_whatsapp,
            'alamat_pengiriman' => $this->alamat_pengiriman,
            'total_harga' => (int) $this->total_harga, // Pastikan integer
            'tanggal_pesan' => $this->tanggal_pesan ? $this->tanggal_pesan->format('Y-m-d') : null,
            'status' => $this->status, // Status pesanan (fulfillment)
            'catatan' => $this->catatan,
            'dibuat_pada' => $this->created_at->toISOString(), // Format ISO 8601 standar API
            'diupdate_pada' => $this->updated_at->toISOString(),

            // --- Field terkait pembayaran ---
            'status_pembayaran' => $this->status_pembayaran,
            'metode_pembayaran' => $this->metode_pembayaran,
            'midtrans_order_id' => $this->midtrans_order_id,
            'midtrans_transaction_id' => $this->midtrans_transaction_id,
            // --------------------------------

            // Sertakan data user jika relasi 'user' sudah di-load
            'user' => $this->whenLoaded('user', function () {
                // Pastikan model User ada dan relasi 'user' di Pesanan benar
                if ($this->user) {
                    return [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ];
                }
                return null; // Kembalikan null jika user tidak ada (misal: guest order)
            }),

            // Sertakan detail item ikan yang dipesan jika relasi 'items' sudah di-load
            'items' => $this->whenLoaded('items', function () {
                // $this->items adalah collection dari model Ikan
                return $this->items->map(function ($ikan) {
                    // Akses data pivot untuk jumlah dan harga saat pesan
                    $pivotData = $ikan->pivot;
                    // Safety check jika karena alasan aneh pivot tidak ter-load
                    if (!$pivotData || !isset($pivotData->jumlah) || !isset($pivotData->harga_saat_pesan)) {
                        Log::warning("Data pivot tidak lengkap atau tidak ada untuk ikan ID {$ikan->id} pada pesanan ID {$this->id}");
                        return [ // Kembalikan data dasar saja atau null/kosong
                            'ikan_id' => $ikan->id,
                            'nama_ikan' => $ikan->nama_ikan ?? 'Error: Nama tidak ada',
                            'gambar_utama' => $ikan->gambar_utama ?? null,
                            'slug' => $ikan->slug ?? null,
                            'jumlah' => 0,
                            'harga_saat_pesan' => 0,
                            'subtotal' => 0,
                        ];
                    }

                    $jumlah = (int) $pivotData->jumlah;
                    $harga = (int) $pivotData->harga_saat_pesan;
                    $subtotal = $harga * $jumlah;

                    return [
                        'ikan_id' => $ikan->id,
                        'nama_ikan' => $ikan->nama_ikan ?? 'Nama Produk Tidak Ada', // Ganti jika nama properti berbeda
                        'gambar_utama' => $ikan->gambar_utama ?? null, // Sertakan gambar jika perlu
                        'slug' => $ikan->slug ?? null, // Sertakan slug jika perlu
                        'jumlah' => $jumlah, // Ambil dari pivot
                        'harga_saat_pesan' => $harga, // Ambil dari pivot
                        'subtotal' => $subtotal // Hitung subtotal per item
                    ];
                }); // filter() dihapus agar tidak menghilangkan item jika pivot bermasalah, tapi beri nilai default
            }),
        ];
    }
}