<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_keranjang_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('keranjang_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ikan_id')->constrained('ikan')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'ikan_id']); // Opsional: Hanya satu baris per user per ikan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keranjang_items');
    }
};