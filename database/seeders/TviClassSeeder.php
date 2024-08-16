<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TviClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            ['name' => 'NGA', 'tvi_type_id' => 1],
            ['name' => 'LGU', 'tvi_type_id' => 1],
            ['name' => 'LUC', 'tvi_type_id' => 1],
            ['name' => 'SUC', 'tvi_type_id' => 1],
            ['name' => 'TTI', 'tvi_type_id' => 1],
            ['name' => 'HEI', 'tvi_type_id' => 2],
            ['name' => 'TVI', 'tvi_type_id' => 2],
            ['name' => 'NGO', 'tvi_type_id' => 2],
        ];

        foreach ($classes as $class) {
            DB::table('tvi_classes')->updateOrInsert($class);
        }
    }
}
