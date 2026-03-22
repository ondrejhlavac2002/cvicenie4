<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('notes')->insert([
            [
                'user_id' => 3,
                'title' => 'Shopping List',
                'body' => 'Mlieko, chlieb, vajcia',
                'status' => 'draft',
                'is_pinned' => false,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'user_id' => 2,
                'title' => 'Projekt – Laravel API',
                'body' => 'Dokončiť migrácie a seedery. Pridať autentifikáciu.',
                'status' => 'published',
                'is_pinned' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'user_id' => 2,
                'title' => 'Nápad na aplikáciu',
                'body' => 'Mobilná aplikácia na sledovanie výdavkov.',
                'status' => 'draft',
                'is_pinned' => false,
                'created_at' => $now->copy()->subDays(35),
                'updated_at' => $now->copy()->subDays(35),
                'deleted_at' => null,
            ],
            [
                'user_id' => 4,
                'title' => 'Stretnutie tímu',
                'body' => 'Pondelok 10:00, zasadačka č. 3.',
                'status' => 'published',
                'is_pinned' => false,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'user_id' => 5,
                'title' => 'Prednáška – Databázy',
                'body' => 'Kapitola 5 – Normalizácia.',
                'status' => 'archived',
                'is_pinned' => false,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);
    }
}
