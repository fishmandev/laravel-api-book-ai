<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'id' => 1,
            'name' => 'System Administrator',
            'email' => 'system@example.com',
            'password' => Hash::make('system123'),
            'email_verified_at' => now(),
        ]);
    }
}
