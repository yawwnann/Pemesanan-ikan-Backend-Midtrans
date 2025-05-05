<?php

// File: app/Http/Controllers/Api/IkanController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IkanResource;
use App\Http\Resources\KategoriResource; // Pastikan ini di-import jika method daftarKategori dipakai
use App\Models\Ikan;
use App\Models\KategoriIkan; // Pastikan ini di-import jika method daftarKategori dipakai
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder; // Import Builder untuk type hinting closure

class IkanController extends Controller
{
    /**
     * Menampilkan daftar semua ikan dengan paginasi, filter, sort, dan search.
     */
    public function index(Request $request)
    {
        // Validasi input sederhana (opsional tapi bagus)
        $request->validate([
            'q' => 'nullable|string|max:100',
            // Ganti 'nama' dengan nama kolom yang benar jika berbeda
            'sort' => 'nullable|string|in:harga,created_at,nama_ikan',
            'order' => 'nullable|string|in:asc,desc',
            'status_ketersediaan' => 'nullable|string|in:tersedia,habis',
            'kategori_slug' => 'nullable|string|exists:kategori_ikan,slug'
        ]);

        // Ambil nilai dari request query
        $searchQuery = $request->query('q');
        // Ganti 'nama_ikan' dengan nama kolom yang benar jika berbeda
        $sortBy = $request->query('sort', 'created_at'); // Default sort by tanggal dibuat
        $sortOrder = $request->query('order', 'desc'); // Default order descending (terbaru)
        $statusKetersediaan = $request->query('status_ketersediaan');
        $kategoriSlug = $request->query('kategori_slug');

        // Mulai query dengan eager loading kategori
        $ikanQuery = Ikan::with('kategori');

        // 1. Terapkan Filter Status Ketersediaan
        if ($statusKetersediaan) {
            if (strtolower($statusKetersediaan) === 'tersedia') {
                $ikanQuery->where('stok', '>', 0);
                // Jika ada field 'status_ketersediaan' string:
                // $ikanQuery->where('status_ketersediaan', 'Tersedia');
            } elseif (strtolower($statusKetersediaan) === 'habis') {
                $ikanQuery->where('stok', '<=', 0);
                // Jika ada field 'status_ketersediaan' string:
                // $ikanQuery->where('status_ketersediaan', 'Habis');
            }
        }
        // Jika tidak ada filter status, tidak ada where stok tambahan
        // (menampilkan semua termasuk yang habis)

        // 2. Terapkan Filter Kategori (jika ada)
        if ($kategoriSlug) {
            // Pastikan relasi 'kategori' dan kolom 'slug' di tabel kategori sudah benar
            $ikanQuery->whereHas('kategori', function (Builder $query) use ($kategoriSlug) {
                $query->where('slug', $kategoriSlug);
            });
        }

        // 3. Terapkan Pencarian (Search)
        if ($searchQuery) {
            $ikanQuery->where(function (Builder $query) use ($searchQuery) {
                // Ganti 'nama_ikan' dengan nama kolom yang benar jika berbeda
                $query->where('nama_ikan', 'LIKE', "%{$searchQuery}%")
                    ->orWhere('deskripsi', 'LIKE', "%{$searchQuery}%");
            });
        }

        // 4. Terapkan Sorting (Urutan)
        // Validasi field yang boleh di-sort
        // Ganti 'nama_ikan' dengan nama kolom yang benar jika berbeda
        $allowedSorts = ['harga', 'created_at', 'nama_ikan'];
        $sortField = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDirection = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        $ikanQuery->orderBy($sortField, $sortDirection);

        // Tambahkan sort kedua by nama untuk konsistensi jika sort utama sama
        if ($sortField !== 'nama_ikan') { // <-- GANTI 'nama_ikan' jika perlu
            $ikanQuery->orderBy('nama_ikan', 'asc'); // <-- GANTI 'nama_ikan' jika perlu
        }

        // 5. Ambil data dengan paginasi + sertakan query string
        $ikan = $ikanQuery->paginate(12)->withQueryString();

        // Kembalikan sebagai resource collection
        return IkanResource::collection($ikan);
    }

    /**
     * Menampilkan detail satu ikan berdasarkan slug.
     * Method ini menggunakan Route Model Binding. Pastikan route Anda di api.php didefinisikan dengan benar
     * misalnya: Route::get('/ikan/{ikan:slug}', [IkanController::class, 'show']);
     */
    public function show(Ikan $ikan) // Pastikan type hint Ikan sudah benar
    {
        // Load relasi kategori jika belum otomatis ter-load (tergantung $with di Model)
        $ikan->loadMissing('kategori');

        // Kembalikan sebagai resource tunggal
        return new IkanResource($ikan);
    }

    /**
     * Menampilkan daftar semua kategori ikan.
     * Pastikan Model KategoriIkan dan KategoriResource ada dan benar.
     */
    public function daftarKategori()
    {
        // Pastikan nama kolom untuk nama kategori benar ('nama_kategori'?)
        $kategori = KategoriIkan::orderBy('nama', 'asc')->get(); // Asumsi nama kolom kategori adalah 'nama'

        // Kembalikan sebagai resource collection
        return KategoriResource::collection($kategori);
    }
}