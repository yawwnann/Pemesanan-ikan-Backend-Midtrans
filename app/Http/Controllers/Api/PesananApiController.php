<?php

// File: app/Http/Controllers/Api/PesananApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePesananApiRequest;
use App\Http\Resources\PesananResource;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
        $pesanan = Pesanan::with(['user', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

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
            $user = $request->user();
            $pesanan = $pesananService->createOrder($validatedData, $user);
            $pesanan->load(['user', 'items']);

            return (new PesananResource($pesanan))->response()->setStatusCode(201);

        } catch (Exception $e) {
            Log::error('API Pesanan Store Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal membuat pesanan.',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Menampilkan detail satu pesanan.
     * GET /api/pesanan/{pesanan}
     */
    public function show(Request $request, Pesanan $pesanan): JsonResponse
    {
        $pesanan->load(['user', 'items.ikan']);

        return (new PesananResource($pesanan))->response();
    }

    /**
     * Mengupdate data pesanan.
     * PUT/PATCH /api/pesanan/{pesanan}
     */
    public function update(Request $request, Pesanan $pesanan, PesananService $pesananService): JsonResponse
    {
        $validatedData = $request->all();

        try {
            $updatedPesanan = $pesananService->updateOrder($pesanan, $validatedData);
            $updatedPesanan->load(['user', 'items']);

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
    public function destroy(Request $request, Pesanan $pesanan): JsonResponse
    {
        try {
            $isDeleted = $pesanan->delete();

            if ($isDeleted) {
                return response()->json(['message' => 'Pesanan berhasil dihapus.'], 200);
            } else {
                throw new Exception("Gagal menghapus pesanan.");
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
