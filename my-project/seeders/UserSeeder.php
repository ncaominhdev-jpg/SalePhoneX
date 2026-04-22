<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
           User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'phone' => '0123456789',
        'status' => true,
        'role' => 'ADMIN',
        'branch_id' => 1,
        'email_verified_at' => now(),
        'password' => Hash::make('password'), // Mật khẩu: password
        'remember_token' => Str::random(10),
    ]);
    }
}
