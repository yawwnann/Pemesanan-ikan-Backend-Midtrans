<?php

// File: routes/api.php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IkanController;
use App\Http\Controllers\Api\PesananApiController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| This is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group. Make something great!
|
*/

// == API Endpoints Katalog Ikan (Publik) ==

// Endpoint untuk mendapatkan daftar kategori ikan
Route::get('/kategori', [IkanController::class, 'daftarKategori'])->name('api.kategori.index');

// Endpoint untuk mendapatkan daftar ikan
Route::get('/ikan', [IkanController::class, 'index'])->name('api.ikan.index');

// Endpoint untuk mendapatkan informasi ikan berdasarkan slug
Route::get('/ikan/{ikan:slug}', [IkanController::class, 'show'])->name('api.ikan.show');


// == API Endpoints Otentikasi (Publik) ==

// Endpoint untuk melakukan registrasi pengguna
Route::post('/register', [AuthController::class, 'register'])->name('api.register');

// Endpoint untuk login dan mendapatkan token autentikasi
Route::post('/login', [AuthController::class, 'login'])->name('api.login');


// == API Endpoints yang Memerlukan Otentikasi (Sanctum Token) ==

// Semua endpoint yang memerlukan otentikasi berada di dalam grup ini
Route::middleware('auth:sanctum')->group(function () {

    // Endpoint untuk melakukan logout dan menghapus token
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Endpoint untuk mendapatkan data pengguna yang sedang login
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');

    // Endpoint untuk membuat pesanan baru
    Route::post('/pesanan', [PesananApiController::class, 'store'])->name('api.pesanan.store');

    //untuk mengelola pesanan:
    // Route::get('/pesanan', [PesananApiController::class, 'index'])->name('api.pesanan.index');
    // Route::get('/pesanan/{pesanan}', [PesananApiController::class, 'show'])->name('api.pesanan.show');
    // Route::put('/pesanan/{pesanan}', [PesananApiController::class, 'update'])->name('api.pesanan.update');
    // Route::delete('/pesanan/{pesanan}', [PesananApiController::class, 'destroy'])->name('api.pesanan.destroy');

    // Letakkan endpoint API lain yang memerlukan user login di dalam grup ini

});

// Route fallback jika endpoint API tidak ditemukan (opsional)
// Jika endpoint yang diminta tidak ada, akan memberikan respons error 404
Route::fallback(function () {
    return response()->json(['message' => 'Endpoint tidak ditemukan.'], 404);
});
Route::middleware('auth:sanctum')->group(function () {
    // ... (route API terotentikasi lainnya seperti /api/user)
    Route::post('/user/profile-photo', [UserProfileController::class, 'updateProfilePhoto'])->name('user.photo.update');
    // Tambahkan route untuk menghapus foto jika perlu
    // Route::delete('/user/profile-photo', [UserProfileController::class, 'deleteProfilePhoto'])->name('user.photo.delete');
});