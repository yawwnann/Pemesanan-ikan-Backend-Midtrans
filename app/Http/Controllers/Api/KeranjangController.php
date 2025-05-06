<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// Hapus DB jika tidak dipakai lagi di method lain
// use Illuminate\Support\Facades\DB;
use App\Models\KeranjangItem; // Import Model KeranjangItem
use App\Models\Ikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\KeranjangItemResource; // Import Resource
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder; // Import Builder jika dipakai di tempat lain

class KeranjangController extends Controller
{
    /**
     * Display a listing of the resource (tampilkan isi keranjang user).
     * (Versi menggunakan Eloquent & Resource)
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Menggunakan Eloquent Relationship dan Eager Loading
        // Memastikan relasi 'ikan' dan 'kategori' di dalam 'ikan' juga di-load
        $keranjangItems = $user->keranjangItems()->with([
            'ikan' => function ($query) {
                $query->with('kategori'); // Asumsi relasi 'kategori' ada di Model Ikan
            }
        ])->get();

        // Mengembalikan data menggunakan API Resource Collection
        // Ini akan otomatis membuat struktur dengan key 'data' dan format sesuai Resource
        return KeranjangItemResource::collection($keranjangItems);
    }

    // Method store, update, destroy sebaiknya juga diubah menggunakan Eloquent
    // agar lebih konsisten dan memanfaatkan fitur Model (misal $fillable, events)
    // Contoh store dengan Eloquent (menggantikan versi DB::table):
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'ikan_id' => ['required', Rule::exists('ikan', 'id')->where(fn($query) => $query->where('stok', '>', 0))],
            'quantity' => 'required|integer|min:1',
        ]);

        $ikanId = $validated['ikan_id'];
        $quantity = $validated['quantity'];
        $ikan = Ikan::find($ikanId);

        if (!$ikan || $ikan->stok < $quantity) {
            return response()->json(['message' => 'Stok ikan tidak mencukupi atau ikan tidak ditemukan.'], 422);
        }

        // Gunakan Eloquent untuk mencari atau membuat item baru
        $keranjangItem = $user->keranjangItems()
            ->where('ikan_id', $ikanId)
            ->first();

        if ($keranjangItem) {
            // Item sudah ada, tambah quantity
            $newQuantity = $keranjangItem->quantity + $quantity;
            // Validasi stok lagi untuk total quantity
            if ($newQuantity > $ikan->stok) {
                return response()->json(['message' => 'Stok ikan tidak cukup untuk jumlah ini.', 'stok_tersisa' => $ikan->stok], 422);
            }
            $keranjangItem->quantity = $newQuantity;
            $keranjangItem->save();
        } else {
            // Buat item baru
            $keranjangItem = $user->keranjangItems()->create([
                'ikan_id' => $ikanId,
                'quantity' => $quantity,
            ]);
        }

        // Load relasi dan kembalikan dengan Resource
        return new KeranjangItemResource($keranjangItem->load('ikan.kategori'));
    }


    // Contoh update dengan Eloquent (menggantikan versi DB::table)
    public function update(Request $request, KeranjangItem $keranjangItem) // Gunakan Route Model Binding
    {
        $user = Auth::user();
        if (!$user || $keranjangItem->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate(['quantity' => 'required|integer|min:1']);
        $ikan = $keranjangItem->ikan; // Ambil ikan dari relasi

        if (!$ikan || $ikan->stok < $validated['quantity']) {
            return response()->json(['message' => 'Stok ikan tidak mencukupi.'], 422);
        }

        $keranjangItem->update(['quantity' => $validated['quantity']]); // Gunakan update()

        return new KeranjangItemResource($keranjangItem->load('ikan.kategori'));
    }


    // Contoh destroy dengan Eloquent (menggantikan versi DB::table)
    public function destroy(KeranjangItem $keranjangItem) // Gunakan Route Model Binding
    {
        $user = Auth::user();
        if (!$user || $keranjangItem->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $keranjangItem->delete();
        return response()->json(['message' => 'Item berhasil dihapus'], 200);
    }

}