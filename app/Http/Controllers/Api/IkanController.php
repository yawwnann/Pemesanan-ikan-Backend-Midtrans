<?php

// File: app/Http/Controllers/Api/IkanController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IkanResource;
use App\Http\Resources\KategoriResource;
use App\Models\Ikan;
use App\Models\KategoriIkan;
use Illuminate\Http\Request;

class IkanController extends Controller
{
    /**
     * Menampilkan daftar semua ikan dengan paginasi.
     */
    public function index(Request $request)
    {
        // Eager load relasi kategori dan filter stok > 0
        $ikanQuery = Ikan::with('kategori')->where('stok', '>', 0);

        // Filter berdasarkan kategori_slug jika ada
        if ($request->has('kategori_slug')) {
            $ikanQuery->whereHas('kategori', function ($query) use ($request) {
                $query->where('slug', $request->query('kategori_slug'));
            });
        }

        // Ambil data ikan dengan paginasi
        $ikan = $ikanQuery->orderBy('nama_ikan', 'asc')->paginate(12);

        // Kembalikan sebagai resource collection dengan info paginasi
        return IkanResource::collection($ikan);
    }

    /**
     * Menampilkan detail satu ikan berdasarkan slug.
     */
    public function show(Ikan $ikan)
    {
        // Load relasi kategori jika belum ada
        $ikan->loadMissing('kategori');

        // Kembalikan sebagai resource tunggal
        return new IkanResource($ikan);
    }

    /**
     * Menampilkan daftar semua kategori ikan.
     */
    public function daftarKategori()
    {
        // Ambil semua kategori ikan
        $kategori = KategoriIkan::orderBy('nama_kategori', 'asc')->get();

        // Kembalikan sebagai resource collection
        return KategoriResource::collection($kategori);
    }
}
