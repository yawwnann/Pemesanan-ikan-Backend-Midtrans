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
        Schema::create('ikan', function (Blueprint $table) {
            $table->id(); // UNSIGNED BIGINT, Auto Increment, Primary Key

            // Foreign key ke tabel kategori_ikan
            $table->foreignId('kategori_id')
                ->constrained('kategori_ikan')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->string('nama_ikan', 150);
            $table->string('slug', 170)->unique();
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 15, 0);
            $table->string('status_ketersediaan', 50)->default('Tersedia');
            $table->string('gambar_utama', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ikan');
    }
};