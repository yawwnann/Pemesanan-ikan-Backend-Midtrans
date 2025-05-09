<?php

// File: app/Http/Controllers/Api/PesananApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePesananApiRequest; // Validasi Request
use App\Http\Resources\PesananResource;      // Resource untuk format output (jika digunakan)
use App\Models\Pesanan;                       // Model Pesanan
use App\Services\PesananService;              // Service untuk logika bisnis
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;                                // Import Exception

class PesananApiController extends Controller
{
    /**
     * Menampilkan daftar pesanan (dengan paginasi).
     * GET /api/pesanan
     */
    public function index(Request $request): JsonResponse
    {
        // Ambil user yang sedang login
        $user = $request->user();

        // Ambil pesanan milik user tersebut
        $pesanan = Pesanan::where('user_id', $user->id) // Filter berdasarkan user_id
            ->with(['user', 'items']) // Eager load items dan relasi ikan di dalam items
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15)); // Default 15 per halaman

        // Gunakan PesananResource jika Anda ingin mentransformasi output
        // Jika tidak, Anda bisa langsung return $pesanan
        return PesananResource::collection($pesanan)->response();
    }


    /**
     * Menyimpan pesanan baru dan mengembalikan Snap Token Midtrans.
     * POST /api/pesanan
     */
    public function store(StorePesananApiRequest $request, PesananService $pesananService): JsonResponse
    {
        // Validasi otomatis oleh StorePesananApiRequest sudah berjalan

        $validatedData = $request->validated(); // Ambil data yang sudah divalidasi

        try {
            $user = $request->user(); // Dapatkan user yang terotentikasi

            // 1. Buat Pesanan menggunakan Service
            // Method createOrder sekarang diharapkan menghitung total, membuat Midtrans Order ID,
            // memindahkan item keranjang, dan mengosongkan keranjang.
            $pesanan = $pesananService->createOrder($validatedData, $user);

            // 2. Dapatkan Snap Token untuk pesanan yang baru dibuat dari Service
            $snapToken = $pesananService->getMidtransSnapToken($pesanan);

            // 3. Berhasil: Kembalikan ID Pesanan dan Snap Token ke frontend
            // Frontend membutuhkan snap_token untuk membuka popup Midtrans
            // Mengirim ID pesanan juga berguna untuk referensi di frontend
            return response()->json([
                'message' => 'Pesanan berhasil dibuat, silahkan lanjutkan pembayaran.',
                'order_id' => $pesanan->midtrans_order_id, // ID Pesanan yang dipakai Midtrans
                'pesanan_id' => $pesanan->id,             // ID internal pesanan (jika perlu)
                'snap_token' => $snapToken
            ], 201); // Status 201 Created

        } catch (Exception $e) {
            // Tangani error dari PesananService (baik createOrder atau getMidtransSnapToken)
            Log::error('API Pesanan Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString() // Log trace untuk debugging
            ]);

            // Kirim respons error yang sesuai
            // Jika error karena keranjang kosong (dari service atau validasi), kembalikan 422
            if (str_contains($e->getMessage(), 'Keranjang kosong') || str_contains($e->getMessage(), 'Minimal ada satu item')) {
                return response()->json([
                    'message' => 'Keranjang Anda kosong atau item tidak valid.',
                    'errors' => ['keranjang' => [$e->getMessage()]]
                ], 422); // Unprocessable Content
            }

            // Error umum lainnya saat membuat pesanan atau token
            return response()->json([
                'message' => 'Gagal memproses pesanan.',
                'error' => $e->getMessage() // Kirim pesan error general atau spesifik jika aman
            ], 500); // Internal Server Error
        }
    }

    /**
     * Menampilkan detail satu pesanan.
     * GET /api/pesanan/{pesanan}
     */
    public function show(Request $request, Pesanan $pesanan): JsonResponse
    {
        // Pastikan user hanya bisa melihat pesanannya sendiri (Otorisasi)
        if ($request->user()->id !== $pesanan->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Eager load relasi yang dibutuhkan
        $pesanan->load(['user', 'items']); // Load user dan items beserta detail ikannya

        // Gunakan PesananResource jika Anda ingin mentransformasi output
        return (new PesananResource($pesanan))->response();
    }


    /**
     * Mengupdate data pesanan.
     * PUT/PATCH /api/pesanan/{pesanan}
     * (Biasanya untuk update status oleh admin, atau mungkin pembatalan oleh user)
     */
    public function update(Request $request, Pesanan $pesanan, PesananService $pesananService): JsonResponse
    {
        // Implementasi otorisasi (misal: hanya admin atau user pemilik)
        if ($request->user()->id !== $pesanan->user_id /* && !$request->user()->isAdmin() */) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Anda mungkin perlu Form Request terpisah untuk update (misal: UpdatePesananApiRequest)
        // Untuk contoh, kita ambil semua data request
        $validatedData = $request->all(); // Hati-hati dengan Mass Assignment, validasi diperlukan!

        try {
            // Panggil service untuk update (jika logika kompleks)
            $updatedPesanan = $pesananService->updateOrder($pesanan, $validatedData); // Pastikan method ini ada di service
            $updatedPesanan->load(['user', 'items']);

            return (new PesananResource($updatedPesanan))->response();

        } catch (Exception $e) {
            Log::error('API Pesanan Update Error for Pesanan ID [' . $pesanan->id . ']: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengupdate pesanan.',
                'error' => $e->getMessage()
            ], 422); // Atau 500
        }
    }

    /**
     * Menghapus data pesanan.
     * DELETE /api/pesanan/{pesanan}
     * (Hati-hati menggunakan ini, mungkin lebih baik soft delete atau pembatalan)
     */
    public function destroy(Request $request, Pesanan $pesanan): JsonResponse
    {
        // Implementasi otorisasi
        if ($request->user()->id !== $pesanan->user_id /* && !$request->user()->isAdmin() */) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Pertimbangkan apakah pesanan boleh dihapus (misal: hanya jika status tertentu)
            // if ($pesanan->status !== 'Dibatalkan') {
            //     throw new Exception("Hanya pesanan yang dibatalkan yang bisa dihapus.");
            // }

            $isDeleted = $pesanan->delete(); // Ini hard delete

            if ($isDeleted) {
                return response()->json(['message' => 'Pesanan berhasil dihapus.'], 200);
            } else {
                throw new Exception("Gagal menghapus pesanan dari database.");
            }

        } catch (Exception $e) {
            Log::error('API Pesanan Destroy Error for Pesanan ID [' . $pesanan->id . ']: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menghapus pesanan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}