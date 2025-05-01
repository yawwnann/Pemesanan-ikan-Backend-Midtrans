<?php
// File: app/Http/Controllers/Api/IkanController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IkanResource; // Import IkanResource
use App\Http\Resources\KategoriResource; // Import KategoriResource
use App\Models\Ikan;
use App\Models\KategoriIkan;
use Illuminate\Http\Request;

class IkanController extends Controller
{
    /**
     * Menampilkan daftar semua ikan (dengan paginasi).
     */
    public function index(Request $request)
    {
        // Eager load relasi kategori untuk efisiensi
        $ikanQuery = Ikan::with('kategori')->where('stok', '>', 0); // Hanya tampilkan yg ada stok

        // Contoh filter berdasarkan kategori (opsional)
        if ($request->has('kategori_slug')) {
            $ikanQuery->whereHas('kategori', function ($query) use ($request) {
                $query->where('slug', $request->query('kategori_slug'));
            });
        }

        // Ambil data dengan paginasi (misal 12 per halaman)
        $ikan = $ikanQuery->orderBy('nama_ikan', 'asc')->paginate(12);

        // Kembalikan sebagai collection resource (otomatis menyertakan info paginasi)
        return IkanResource::collection($ikan);
    }

    /**
     * Menampilkan detail satu ikan berdasarkan slug.
     */
    public function show(Ikan $ikan) // Gunakan Route Model Binding
    {
        // Load relasi kategori jika belum
        $ikan->loadMissing('kategori');

        // Kembalikan sebagai single resource
        return new IkanResource($ikan);
    }

    /**
     * (Opsional) Menampilkan daftar semua kategori.
     */
    public function daftarKategori()
    {
        $kategori = KategoriIkan::orderBy('nama_kategori', 'asc')->get();
        return KategoriResource::collection($kategori);
    }
}