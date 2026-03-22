<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NoteCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('note_category')->insert([
            ['note_id' => 1, 'category_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['note_id' => 1, 'category_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['note_id' => 2, 'category_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['note_id' => 2, 'category_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['note_id' => 3, 'category_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['note_id' => 4, 'category_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['note_id' => 5, 'category_id' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
