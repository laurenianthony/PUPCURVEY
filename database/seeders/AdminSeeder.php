<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@pupulse.edu.ph'],
            [
                'name' => 'PUPulse Super Admin',
                'student_id' => null,
                'password' => Hash::make('SuperAdmin@2026!'),
                'program' => 'ADMIN',
                'year_level' => null,
                'role' => 'super_admin',
                'status' => 'active',
                'wallet_balance' => 0,
            ],
        );
    }
}
