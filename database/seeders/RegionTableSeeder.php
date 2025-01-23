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
            
            ['name' => 'NCR', 'code' => '13'],
            ['name' => 'CAR','code' => '14'],
            ['name' => 'Region I', 'code' => '01'],
            ['name' => 'Region II', 'code' => '02'],
            ['name' => 'Region III','code' => '03'],
            ['name' => 'Region IV-A','code' => '04'],
            ['name' => 'Region IV-B','code' => '17'],
            ['name' => 'Region V','code' => '05'],
            ['name' => 'Region VI','code' => '06'],
            ['name' => 'Negros Island Region','code' => '18'],
            ['name' => 'Region VII','code' => '07'],
            ['name' => 'Region VIII','code' => '08'],
            ['name' => 'Region IX','code' => '09'],
            ['name' => 'Region X','code' => '10'],
            ['name' => 'Region XI','code' => '11'],
            ['name' => 'Region XII','code' => '12'],
            ['name' => 'Region XIII','code' => '16'],
            ['name' => 'BARMM','code' => '19'],
            ['name' => 'Not Applicable', 'code' => '00'],
        ];

        foreach ($regions as $region) {
            DB::table('regions')->updateOrInsert($region);
        }
    }
}