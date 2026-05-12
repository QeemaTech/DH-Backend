<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'phone' => '01234567890',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'is_active' => true,
            'is_verified' => true,
        ]);

        $user->assignRole('admin');

    }
}
