<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $regions = [
            ['name' => 'NCR', 'code' => '1300000000'],
            ['name' => 'CAR', 'code' => '1400000000'],
            ['name' => 'Region I', 'code' => '0100000000'],
            ['name' => 'Region II', 'code' => '0200000000'],
            ['name' => 'Region III', 'code' => '0300000000'],
            ['name' => 'Region IV-A', 'code' => '0400000000'],
            ['name' => 'Region IV-B', 'code' => '1700000000'],
            ['name' => 'Region V', 'code' => '0500000000'],
            ['name' => 'Region VI', 'code' => '0600000000'],
            ['name' => 'Negros Island Region', 'code' => '1800000000'],
            ['name' => 'Region VII', 'code' => '0700000000'],
            ['name' => 'Region VIII', 'code' => '0800000000'],
            ['name' => 'Region IX', 'code' => '0900000000'],
            ['name' => 'Region X', 'code' => '1000000000'],
            ['name' => 'Region XI', 'code' => '1100000000'],
            ['name' => 'Region XII', 'code' => '1200000000'],
            ['name' => 'CARAGA', 'code' => '1600000000'],
            ['name' => 'BARMM', 'code' => '1900000000'],
            ['name' => 'Not Applicable', 'code' => '0000000000'],
        ];

        foreach ($regions as $region) {
            DB::table('regions')->updateOrInsert($region);
        }
    }
}