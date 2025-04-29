<?php // database/migrations/xxxx_..._create_ikan_pesanan_pivot_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ikan_pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')->cascadeOnDelete(); // Jika pesanan dihapus, item ikut hilang
            $table->foreignId('ikan_id')->constrained('ikan')->cascadeOnDelete(); // Jika ikan dihapus, item ikut hilang
            $table->unsignedInteger('jumlah')->default(1); // Jumlah ikan jenis ini yg dipesan
            $table->decimal('harga_saat_pesan', 15, 0)->nullable(); // Simpan harga saat itu (opsional tp bagus)
            $table->timestamps(); // Opsional, jika perlu tahu kapan item ditambah
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ikan_pesanan');
    }
};