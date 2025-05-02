<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/pesanan/{pesanan}/pdf', [PdfController::class, 'downloadPesananPdf'])
    ->name('pesanan.pdf');