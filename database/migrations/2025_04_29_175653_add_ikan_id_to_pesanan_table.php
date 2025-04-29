<?php
// database/migrations/xxxx_..._add_ikan_id_to_pesanan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Tambahkan foreign key ke tabel ikan
            $table->foreignId('ikan_id')
                ->nullable() // Buat nullable jika pesanan bisa tanpa ikan spesifik? Atau constrained() jika wajib
                ->constrained('ikan') // Nama tabel ikan
                ->nullOnDelete() // Jika ikan dihapus, set ikan_id jadi NULL di pesanan ini
                ->after('nomor_whatsapp'); // Letakkan setelah nomor whatsapp
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropForeign(['ikan_id']); // Hapus constraint dulu
            $table->dropColumn('ikan_id');   // Hapus kolomnya
        });
    }
};