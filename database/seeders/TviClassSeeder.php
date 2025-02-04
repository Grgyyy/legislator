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
            ['name' => 'NGA'],
            ['name' => 'LGU'],
            ['name' => 'LUC'],
            ['name' => 'SUC'],
            ['name' => 'TTI'],
            ['name' => 'HEI'],
            ['name' => 'TVI'],
            ['name' => 'NGO'],
        ];

        foreach ($classes as $class) {
            DB::table('tvi_classes')->updateOrInsert($class);
        }
    }
}
