<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@quincsaas.com'],
            [
                'name' => 'Super Administrateur',
                'password' => Hash::make('Admin@2026!'),
                'is_super_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
