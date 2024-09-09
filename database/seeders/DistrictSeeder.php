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
        $municipalities = DB::table('municipalities')
            ->where('name', 'Not Applicable')
            ->pluck('id');

        foreach ($municipalities as $municipalityId) {
            $districtExists = DB::table('districts')
                ->where('name', 'Not Applicable')
                ->where('municipality_id', $municipalityId)
                ->exists();

            if (!$districtExists) {
                DB::table('districts')->insert([
                    'name' => 'Not Applicable',
                    'municipality_id' => $municipalityId,
                ]);
            }
        }
    }
}
