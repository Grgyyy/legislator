<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i < 104; $i++) {
            $district = ['Not Applicable', $i];
            $districtExist = DB::table('districts')
                    ->where('name', $district[0])
                    ->where('municipality_id', $i)
                    ->exists();

            if (!$districtExist) {
                        DB::table('districts')->insert([
                            'name' => $district[0],
                            'municipality_id' => $district[1],
                        ]);
            }
        }
    }
}
