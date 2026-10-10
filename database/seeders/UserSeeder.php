<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        User::upsert([
            ['email' => 'demo1@example.com', 'name' => 'Demo User 1', 'email_verified_at' => now(), 'password' => $password],
        ], ['email'], ['name', 'email_verified_at', 'password']);
    }
}
