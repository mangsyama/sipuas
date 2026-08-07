<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            User::create([
                'name' => 'Administrator SIPUAS',
                'username' => 'admin',
                'nip' => '199001012020011001',
                'email' => 'admin@sipuas.local',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);
        }
    }
}
