<?php // database/migrations/xxxx_..._create_roles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'Admin', 'User'
            $table->string('slug')->unique(); // e.g., 'admin', 'user'
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};