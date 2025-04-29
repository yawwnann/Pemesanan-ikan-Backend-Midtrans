<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pelanggan');
            $table->string('nomor_whatsapp')->nullable();
            // Untuk awal, kita buat simpel: daftar ikan disimpan sebagai teks
            $table->text('ikan_dipesan')->nullable();
            $table->decimal('total_harga', 15, 0)->nullable(); // Sesuaikan presisi jika perlu
            $table->date('tanggal_pesan')->nullable(); // Atau pakai dateTime jika perlu jam
            $table->string('status')->default('Baru'); // Contoh status: Baru, Diproses, Dikirim, Selesai, Batal
            $table->text('catatan')->nullable(); // Catatan internal admin
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};