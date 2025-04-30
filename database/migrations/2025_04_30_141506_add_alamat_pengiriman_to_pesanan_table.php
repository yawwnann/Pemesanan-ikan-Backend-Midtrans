<?php
// database/migrations/xxxx_..._add_alamat_pengiriman_to_pesanan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Tambah kolom alamat setelah nomor_whatsapp
            // Tipe TEXT agar bisa panjang, nullable karena mungkin diisi belakangan
            $table->text('alamat_pengiriman')->nullable()->after('nomor_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Hapus kolom jika di-rollback
            $table->dropColumn('alamat_pengiriman');
        });
    }
};