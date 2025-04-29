<?php
// app/Observers/PesananObserver.php
namespace App\Observers;

use App\Models\Pesanan;
use App\Models\Ikan;
use Illuminate\Support\Facades\Log;

class PesananObserver
{
    public function created(Pesanan $pesanan): void
    {
        Log::info("--- PesananObserver@created START for Pesanan ID: {$pesanan->id} ---");
        try {
            // Coba Eager load relasi items (ikan yang ada di pesanan ini)
            $pesanan->load('items');
            $items = $pesanan->items;

            // Cek apakah relasi items berhasil dimuat dan tidak kosong
            if ($items && $items->count() > 0) {
                Log::info("Found {$items->count()} items for Pesanan ID: {$pesanan->id}");

                // Loop melalui setiap item ikan dalam pesanan
                foreach ($items as $item) {
                    // $item adalah objek model Ikan
                    $jumlahDipesan = $item->pivot->jumlah; // Ambil jumlah dari tabel pivot
                    Log::info("Processing Item -> Ikan ID: {$item->id}, Nama: {$item->nama_ikan}, Stok Saat Ini: {$item->stok}, Jumlah Dipesan: {$jumlahDipesan}");

                    // Cek apakah stok mencukupi
                    if ($item->stok >= $jumlahDipesan) {
                        Log::info("Attempting to decrement stock for Ikan ID: {$item->id} by {$jumlahDipesan}");
                        // Lakukan pengurangan stok langsung ke DB
                        $affectedRows = $item->decrement('stok', $jumlahDipesan);
                        Log::info("Decrement result (affected rows): {$affectedRows} for Ikan ID: {$item->id}");

                        // Jika ingin melihat stok terbaru di log (opsional):
                        // $item->refresh();
                        // Log::info("New stock after refresh for Ikan ID {$item->id}: {$item->stok}");

                    } else {
                        // Log jika stok tidak cukup
                        Log::warning("Stok TIDAK CUKUP for Ikan ID {$item->id}. Stok: {$item->stok}, Dipesan: {$jumlahDipesan}");
                    }
                }
            } else {
                // Log jika tidak ada item ditemukan di relasi
                Log::warning("Tidak ada item relasi ditemukan (items) untuk Pesanan ID: {$pesanan->id} saat observer 'created' dijalankan.");
            }
        } catch (\Exception $e) {
            // Tangkap jika ada error tak terduga selama proses observer
            Log::error("Error in PesananObserver@created for Pesanan ID: {$pesanan->id} - " . $e->getMessage(), ['exception' => $e]);
        }
        Log::info("--- PesananObserver@created END for Pesanan ID: {$pesanan->id} ---");
    }

    // Method updated() dan deleted() bisa ditambahkan log serupa jika perlu di-debug nanti
    public function updated(Pesanan $pesanan): void
    { /* ... */
    }
    public function deleted(Pesanan $pesanan): void
    { /* ... */
    }
}