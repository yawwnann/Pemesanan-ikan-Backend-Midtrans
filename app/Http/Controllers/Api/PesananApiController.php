<?php
// File: app/Http/Controllers/Api/PesananApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePesananApiRequest; // <-- Import Form Request
use App\Http\Resources\PesananResource;      // <-- Import Resource Pesanan
use App\Services\PesananService;             // <-- Import Service Pesanan
use Illuminate\Http\JsonResponse;            // <-- Import JsonResponse
use Illuminate\Support\Facades\Log;
use Exception; // <-- Import Exception

class PesananApiController extends Controller
{
    /**
     * Menyimpan pesanan baru yang dibuat via API.
     */
    public function store(StorePesananApiRequest $request, PesananService $pesananService): JsonResponse
    {
        // Ambil data yang sudah divalidasi dari Form Request
        $validatedData = $request->validated();

        try {
            // Panggil service untuk membuat pesanan
            $pesanan = $pesananService->createOrder($validatedData);

            // Kembalikan response sukses dengan data pesanan yang baru dibuat (difformat oleh Resource)
            // Status 201 Created
            return (new PesananResource($pesanan))
                ->response()
                ->setStatusCode(201);

        } catch (Exception $e) {
            // Tangkap error (misal stok tidak cukup dari service, atau error lain)
            Log::error('API Pesanan Store Error: ' . $e->getMessage());

            // Kembalikan response error
            // Status 422 Unprocessable Entity atau 400 Bad Request cocok di sini
            return response()->json([
                'message' => 'Gagal membuat pesanan.',
                'error' => $e->getMessage() // Sertakan pesan error (hati-hati jika sensitif)
            ], 422);
        }
    }

    // Anda bisa tambahkan method lain nanti: index(), show(), update(), destroy()
}