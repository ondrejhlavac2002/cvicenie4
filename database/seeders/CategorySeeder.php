<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('categories')->insert([
            ['name' => 'Práca', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Škola', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Osobné', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nápady', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'TODO', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
