<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::create([
            'name' => 'Admin',
            'email' => 'office@solmana.org',
            'password' => Hash::make('SolmanaAdmin123!'),
            'role' => 'ADMIN',
            'referral_code' => "ADMIN100",
            'email_verified_at' => now(),
            'email_verified' => 'TRUE',
        ]);
    }
}
