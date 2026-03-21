<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Coordinator;
use App\Models\Driver;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        User::create([
            'name' => 'Admin QR',
            'email' => 'admin@qrpackage.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin'
        ]);
    }
}
