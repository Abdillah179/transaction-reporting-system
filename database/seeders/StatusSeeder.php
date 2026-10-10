<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StatusSeeder extends Seeder
{
    public function run(): void
    {

        Status::upsert([
            ['uuid' => (string) Str::uuid7(), 'code' => 'success', 'name' => 'Berhasil'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'pending', 'name' => 'Pending'],
            ['uuid' => (string) Str::uuid7(), 'code' => 'failed', 'name' => 'Gagal'],
        ], ['code'], ['name']);
    }
}
