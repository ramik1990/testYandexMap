<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'admin@example.com')],
            ['name' => 'Admin', 'password' => env('SEED_USER_PASSWORD', 'password')],
        );
    }
}
