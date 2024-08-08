<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $regions = [
            ['name' => 'NCR'],
            ['name' => 'CAR'],
            ['name' => 'BARMM'],
            ['name' => 'Region I'],
            ['name' => 'Region II'],
            ['name' => 'Region III'],
            ['name' => 'Region IV-A'],
            ['name' => 'Region IV-B'],
            ['name' => 'Region V'],
            ['name' => 'Region VI'],
            ['name' => 'Region VII'],
            ['name' => 'Region VIII'],
            ['name' => 'Region IX'],
            ['name' => 'Region X'],
            ['name' => 'Region XI'],
            ['name' => 'Region XII'],
            ['name' => 'Region XIII'],
        ];

        foreach ($regions as $region) {
            DB::table('regions')->insert($region);
        }
    }
}
