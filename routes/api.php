<?php
// File: routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IkanController;
use App\Http\Controllers\Api\PesananApiController; // <-- Import Controller Pesanan

// ... Route Ikan & Kategori ...
Route::get('/ikan', [IkanController::class, 'index'])->name('api.ikan.index');
Route::get('/ikan/{ikan:slug}', [IkanController::class, 'show'])->name('api.ikan.show');
Route::get('/kategori', [IkanController::class, 'daftarKategori'])->name('api.kategori.index');

// === ROUTE BARU UNTUK MEMBUAT PESANAN ===
Route::post('/pesanan', [PesananApiController::class, 'store'])->name('api.pesanan.store');
// ========================================