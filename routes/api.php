<?php

// File: routes/api.php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IkanController;
use App\Http\Controllers\Api\PesananApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KeranjangController;
use App\Http\Controllers\Api\PaymentController;

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
Route::get('/kategori', [IkanController::class, 'daftarKategori'])->name('api.kategori.index');
Route::get('/ikan', [IkanController::class, 'index'])->name('api.ikan.index');
Route::get('/ikan/{ikan:slug}', [IkanController::class, 'show'])->name('api.ikan.show');


// == API Endpoints Otentikasi (Publik) ==
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// == API Endpoint untuk Notifikasi Midtrans (Publik - TIDAK PERLU OTENTIKASI) ==
Route::post('/midtrans/notification', [PaymentController::class, 'handleNotification'])->name('midtrans.notification');


// == API Endpoints yang Memerlukan Otentikasi (Sanctum Token) ==
Route::middleware('auth:sanctum')->group(function () {

    // Otentikasi & User
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::post('/user/profile-photo', [UserProfileController::class, 'updateProfilePhoto'])->name('user.photo.update');
    // Route::delete('/user/profile-photo', [UserProfileController::class, 'deleteProfilePhoto'])->name('user.photo.delete');

    // Pesanan
    Route::post('/pesanan', [PesananApiController::class, 'store'])->name('api.pesanan.store');
    // --- PERBAIKAN: Route GET /pesanan diaktifkan (uncomment) ---
    Route::get('/pesanan', [PesananApiController::class, 'index'])->name('api.pesanan.index');
    Route::get('/pesanan/{pesanan}', [PesananApiController::class, 'show'])->name('api.pesanan.show');
    // Route::put('/pesanan/{pesanan}', [PesananApiController::class, 'update'])->name('api.pesanan.update');
    // Route::delete('/pesanan/{pesanan}', [PesananApiController::class, 'destroy'])->name('api.pesanan.destroy'); 
    // Keranjang
    Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang.index');
    Route::post('/keranjang', [KeranjangController::class, 'store'])->name('keranjang.store');
    Route::put('/keranjang/{keranjangItem}', [KeranjangController::class, 'update'])->name('keranjang.update');
    Route::delete('/keranjang/{keranjangItem}', [KeranjangController::class, 'destroy'])->name('keranjang.destroy');

    // Pembayaran Midtrans (Inisiasi)
    Route::post('/payment/initiate/{pesanan}', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');

});

// Route fallback jika endpoint API tidak ditemukan (opsional)
Route::fallback(function () {
    return response()->json(['message' => 'Endpoint tidak ditemukan.'], 404);
});

