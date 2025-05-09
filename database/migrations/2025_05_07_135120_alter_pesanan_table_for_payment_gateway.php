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
        Schema::table('pesanan', function (Blueprint $table) {
            // Menambahkan kolom metode_pembayaran setelah kolom total_harga (sesuaikan 'total_harga' jika nama kolom Anda berbeda atau ingin posisi lain)
            if (!Schema::hasColumn('pesanan', 'metode_pembayaran')) {
                $table->string('metode_pembayaran', 100)->nullable()->after('total_harga');
            }

            // Menambahkan kolom status_pembayaran setelah metode_pembayaran
            // dengan nilai default 'pending'
            if (!Schema::hasColumn('pesanan', 'status_pembayaran')) {
                $table->string('status_pembayaran', 50)->nullable()->default('pending')->after('metode_pembayaran');
            }

            // Menambahkan kolom midtrans_order_id (ID unik pesanan dari sistem Anda)
            // Kolom ini sebaiknya unik untuk memudahkan pencarian dan menghindari duplikasi referensi
            if (!Schema::hasColumn('pesanan', 'midtrans_order_id')) {
                $table->string('midtrans_order_id')->nullable()->unique()->after('status_pembayaran');
            }

            // Menambahkan kolom midtrans_transaction_id (ID unik transaksi dari Midtrans)
            // Sangat direkomendasikan untuk pelacakan dan rekonsiliasi
            if (!Schema::hasColumn('pesanan', 'midtrans_transaction_id')) {
                $table->string('midtrans_transaction_id')->nullable()->unique()->after('midtrans_order_id');
            }

            // --- OPSIONAL: Mengubah presisi kolom total_harga ---
            // Jika Anda ingin mengubah kolom total_harga dari decimal(15,0) menjadi decimal(15,2)
            // untuk mendukung angka desimal (sen), uncomment baris di bawah.
            // PERHATIAN: Untuk menggunakan ->change(), Anda perlu package doctrine/dbal.
            // Jalankan: composer require doctrine/dbal
            // if (Schema::hasColumn('pesanan', 'total_harga')) {
            //     // Ambil definisi kolom yang ada untuk memastikan kita tidak kehilangan atribut lain secara tidak sengaja
            //     // Namun, cara paling aman adalah jika Anda tahu definisi awalnya (misal nullable, unsigned, dll)
            //     // $table->decimal('total_harga', 15, 2)->nullable(false)->change(); // Sesuaikan nullable() jika perlu
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Drop kolom dalam urutan terbalik atau sebutkan dalam array
            // Hanya drop jika kolomnya memang ada
            $columnsToDrop = [];
            if (Schema::hasColumn('pesanan', 'metode_pembayaran')) {
                $columnsToDrop[] = 'metode_pembayaran';
            }
            if (Schema::hasColumn('pesanan', 'status_pembayaran')) {
                $columnsToDrop[] = 'status_pembayaran';
            }
            if (Schema::hasColumn('pesanan', 'midtrans_order_id')) {
                $columnsToDrop[] = 'midtrans_order_id';
            }
            if (Schema::hasColumn('pesanan', 'midtrans_transaction_id')) {
                $columnsToDrop[] = 'midtrans_transaction_id';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // --- OPSIONAL: Mengembalikan perubahan presisi total_harga ---
            // Jika Anda sebelumnya mengubah total_harga dan ingin mengembalikannya saat rollback.
            // PERHATIAN: Pastikan definisi ini sesuai dengan keadaan SEBELUM migrasi up dijalankan.
            // Ini juga memerlukan doctrine/dbal.
            // if (Schema::hasColumn('pesanan', 'total_harga')) {
            //     $table->decimal('total_harga', 15, 0)->nullable(false)->change(); // Sesuaikan nullable() jika perlu
            // }
        });
    }
};