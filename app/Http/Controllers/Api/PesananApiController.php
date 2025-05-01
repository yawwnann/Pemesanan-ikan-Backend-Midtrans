<?php
// File: app/Http/Controllers/Api/PesananApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePesananApiRequest; // Request untuk Create
// Anda mungkin perlu membuat request terpisah untuk Update nanti:
// use App\Http\Requests\UpdatePesananApiRequest;
use App\Http\Resources\PesananResource;      // Resource untuk format response
use App\Models\Pesanan;                      // Model Pesanan
use App\Services\PesananService;             // Service untuk logika bisnis
use Illuminate\Http\Request;                 // Request standar
use Illuminate\Http\JsonResponse;            // Tipe response
use Illuminate\Support\Facades\Log;
use Exception;

class PesananApiController extends Controller
{
    /**
     * Menampilkan daftar pesanan (dengan paginasi).
     * GET /api/pesanan
     */
    public function index(Request $request): JsonResponse
    {
        // Tambahkan logic otorisasi di sini jika perlu (misal: hanya admin?)
        // if ($request->user()->cannot('viewAny', Pesanan::class)) { abort(403); }

        $pesanan = Pesanan::with(['user', 'items']) // Eager load relasi
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15)); // Ambil per_page dari query ?per_page=30

        return PesananResource::collection($pesanan)->response();
    }

    /**
     * Menyimpan pesanan baru.
     * POST /api/pesanan
     */
    public function store(StorePesananApiRequest $request, PesananService $pesananService): JsonResponse
    {
        $validatedData = $request->validated();
        try {
            $user = $request->user(); // Akan null jika route tidak pakai auth:sanctum
            $pesanan = $pesananService->createOrder($validatedData, $user);
            $pesanan->load(['user', 'items']); // Load relasi untuk response

            return (new PesananResource($pesanan))->response()->setStatusCode(201);

        } catch (Exception $e) {
            Log::error('API Pesanan Store Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal membuat pesanan.',
                'error' => $e->getMessage()
            ], 422); // 422 lebih cocok untuk error validasi/bisnis
        }
    }

    /**
     * Menampilkan detail satu pesanan.
     * GET /api/pesanan/{pesanan}
     */
    public function show(Request $request, Pesanan $pesanan): JsonResponse // Gunakan Route Model Binding
    {
        // Tambahkan logic otorisasi di sini jika perlu (misal: hanya pemilik atau admin?)
        // if ($request->user()->cannot('view', $pesanan)) { abort(403); }

        $pesanan->load(['user', 'items.ikan']); // Load relasi yg dibutuhkan

        return (new PesananResource($pesanan))->response();
    }

    /**
     * Mengupdate data pesanan.
     * PUT/PATCH /api/pesanan/{pesanan}
     */
    public function update(Request $request, Pesanan $pesanan, PesananService $pesananService): JsonResponse
    // Sebaiknya gunakan FormRequest khusus untuk update (misal: UpdatePesananApiRequest)
    {
        // Tambahkan logic otorisasi di sini jika perlu
        // if ($request->user()->cannot('update', $pesanan)) { abort(403); }

        // TODO: Implementasikan validasi yang tepat untuk update
        // $validatedData = $request->validate([...]); // Ganti dengan Form Request
        $validatedData = $request->all(); // HATI-HATI: Ini tidak aman tanpa validasi!

        try {
            $updatedPesanan = $pesananService->updateOrder($pesanan, $validatedData);
            $updatedPesanan->load(['user', 'items']); // Load relasi untuk response

            return (new PesananResource($updatedPesanan))->response();

        } catch (Exception $e) {
            Log::error('API Pesanan Update Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengupdate pesanan.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Menghapus data pesanan.
     * DELETE /api/pesanan/{pesanan}
     */
    public function destroy(Request $request, Pesanan $pesanan /*, PesananService $pesananService */): JsonResponse
    {
        // Tambahkan logic otorisasi di sini jika perlu
        // if ($request->user()->cannot('delete', $pesanan)) { abort(403); }

        try {
            // TODO: Panggil service untuk delete jika ada logika (misal kembalikan stok)
            // $pesananService->deleteOrder($pesanan);

            // Hapus langsung jika tidak ada logika tambahan
            $isDeleted = $pesanan->delete();

            if ($isDeleted) {
                return response()->json(['message' => 'Pesanan berhasil dihapus.'], 200);
                // return response()->noContent(); // Alternatif 204 No Content
            } else {
                throw new Exception("Gagal menghapus pesanan dari database.");
            }

        } catch (Exception $e) {
            Log::error('API Pesanan Destroy Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menghapus pesanan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}