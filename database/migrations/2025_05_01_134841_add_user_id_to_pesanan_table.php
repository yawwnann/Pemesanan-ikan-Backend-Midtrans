<?php
// database/migrations/xxxx...add_user_id_to_pesanan_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Tambahkan foreign key ke tabel users
            $table->foreignId('user_id')
                ->nullable() // <-- Buat nullable, karena admin bisa input pesanan manual tanpa login user
                ->constrained('users') // Nama tabel users
                ->nullOnDelete() // Jika user dihapus, user_id di pesanan jadi NULL
                ->after('id'); // Letakkan setelah kolom ID (atau sesuaikan posisi)
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Cek nama constraint jika berbeda saat drop
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};