<?php
// File: database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Import User Model
use App\Models\Role; // Import Role Model
use Illuminate\Support\Facades\Hash; // Import Hash

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari atau buat user admin utama
        $adminUser = User::updateOrCreate(
            [
                'email' => 'admin@example.com' // Email unik untuk admin
            ],
            [
                'name' => 'Admin Utama',        // Nama admin
                'password' => Hash::make('password123'), // GANTI DENGAN PASSWORD AMAN!
                'email_verified_at' => now()     // Langsung verifikasi email
            ]
        );

        // Cari role 'admin' (pastikan RoleSeeder sudah jalan & ada role dgn slug 'admin')
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminUser && $adminRole) {
            // Hubungkan user dengan role admin (jika belum terhubung)
            // syncWithoutDetaching() aman jika seeder dijalankan berulang kali
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
            $this->command->info('Admin user created/updated and assigned admin role.');
        } elseif (!$adminRole) {
            $this->command->error('Admin role with slug "admin" not found. Please run RoleSeeder first.');
        } else {
            $this->command->error('Failed to create/update admin user.');
        }

        // Anda bisa tambahkan pembuatan user biasa (dengan role 'user') di sini jika perlu
        // User::factory(10)->create()->each(function($user){
        //     $userRole = Role::where('slug', 'user')->first();
        //     if($userRole) {
        //         $user->roles()->attach($userRole->id);
        //     }
        // });
        // $this->command->info('Created 10 dummy users with "user" role.');

    }
}