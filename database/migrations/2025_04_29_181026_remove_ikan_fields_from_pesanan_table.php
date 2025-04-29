<?php // database/migrations/xxxx_..._remove_ikan_fields_from_pesanan_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Hapus foreign key dulu jika ada constraint
            // Cek nama constraint Anda di database jika berbeda
            if (Schema::hasColumn('pesanan', 'ikan_id')) {
                try {
                    $table->dropForeign(['ikan_id']);
                } catch (\Exception $e) {
                } // Coba hapus FK
                $table->dropColumn('ikan_id');
            }
            // Hapus juga kolom teks lama jika tidak dipakai
            if (Schema::hasColumn('pesanan', 'ikan_dipesan')) {
                $table->dropColumn('ikan_dipesan');
            }
        });
    }
    public function down(): void
    { // Kebalikan dari up
        Schema::table('pesanan', function (Blueprint $table) {
            $table->foreignId('ikan_id')->nullable()->constrained('ikan')->nullOnDelete()->after('nomor_whatsapp');
            $table->text('ikan_dipesan')->nullable()->after('ikan_id');
        });
    }
};