<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('users')->insert([
            [
                'first_name' => 'Admin',
                'last_name' => 'Systém',
                'email' => 'admin@nastenka.sk',
                'password' => Hash::make('456'),
                'role' => 'admin',
                'premium_until' => now()->addDays(365),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'first_name' => 'Dávid',
                'last_name' => 'Dvořák',
                'email' => 'ddrzik@ukf.sk',
                'password' => Hash::make('456'),
                'role' => 'user',
                'premium_until' => now()->addDays(30),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'first_name' => 'Jana',
                'last_name' => 'Nováková',
                'email' => 'jana@nastenka.sk',
                'password' => Hash::make('456'),
                'role' => 'user',
                'premium_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'first_name' => 'Peter',
                'last_name' => 'Kováč',
                'email' => 'peter@nastenka.sk',
                'password' => Hash::make('456'),
                'role' => 'user',
                'premium_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'first_name' => 'Mária',
                'last_name' => 'Horáková',
                'email' => 'maria@nastenka.sk',
                'password' => Hash::make('456'),
                'role' => 'user',
                'premium_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
