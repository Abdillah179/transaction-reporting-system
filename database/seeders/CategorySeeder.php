<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {

        Category::upsert([
            ['uuid' => (string) Str::uuid7(), 'code' => 'food', 'name' => 'Makanan & Minuman'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'transport', 'name' => 'Transportasi'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'shopping', 'name' => 'Belanja'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'bills', 'name' => 'Tagihan'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'entertainment', 'name' => 'Hiburan'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'health', 'name' => 'Kesehatan'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'education', 'name' => 'Pendidikan'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'other', 'name' => 'Lainnya'],
        ], ['code'], ['name']);
    }
}
