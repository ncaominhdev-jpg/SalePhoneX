<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'phone' => '0123456789',
            'status' => true,
            'role' => 'ADMIN',
            'branch_id' => null, // hoặc để null nếu chưa có branch
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Mật khẩu: password
            'remember_token' => Str::random(10),
        ]);
    }
}