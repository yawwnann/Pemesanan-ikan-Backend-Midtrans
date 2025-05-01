<?php
// File: routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IkanController;
use App\Http\Controllers\Api\PesananApiController;
use App\Http\Controllers\Api\AuthController; // <-- Import AuthController

// ... Route Ikan, Kategori, Pesanan (POST) ...
Route::get('/ikan', [IkanController::class, 'index'])->name('api.ikan.index');
Route::get('/ikan/{ikan:slug}', [IkanController::class, 'show'])->name('api.ikan.show');
Route::get('/kategori', [IkanController::class, 'daftarKategori'])->name('api.kategori.index');
Route::post('/pesanan', [PesananApiController::class, 'store'])->name('api.pesanan.store');

// --- ROUTE OTENTIKASI ---
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Route yang memerlukan otentikasi (token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');

    // Letakkan route API lain yang butuh login di sini
    // Contoh: Melihat daftar pesanan milik user yang login?
    // Route::get('/pesanan-saya', [PesananApiController::class, 'indexUser']);
});
// --- AKHIR ROUTE OTENTIKASI ---