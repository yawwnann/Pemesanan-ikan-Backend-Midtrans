<?php
// database/seeders/RoleSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Definisikan peran dasar
        $roles = ['admin', 'user']; // Tambah 'pelanggan' atau lain jika perlu

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['slug' => Str::slug($roleName)],
                ['name' => ucfirst($roleName)]
            );
        }
        $this->command->info('Default roles created/ensured.');
    }
}