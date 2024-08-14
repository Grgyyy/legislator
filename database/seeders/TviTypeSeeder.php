<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TviTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $types = [
            ['name' => 'Private'],
            ['name' => 'Public'],
        ];

        foreach ($types as $type) {
            DB::table('tvi_types')->insert($type);
        }

    }
}
