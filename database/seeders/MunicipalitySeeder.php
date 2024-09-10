<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinces = DB::table('provinces')
            ->where('name', 'Not Applicable')
            ->pluck('id');

        foreach ($provinces as $provinceId) {
            $regionId = DB::table('provinces')
                ->where('id', $provinceId)
                ->value('region_id');

            if (!$regionId) {
                continue;
            }

            $municipalityExists = DB::table('municipalities')
                ->where('name', 'Not Applicable')
                ->where('province_id', $provinceId)
                ->exists();

            if (!$municipalityExists) {
                DB::table('municipalities')->insert([
                    'name' => 'Not Applicable',
                    'province_id' => $provinceId,
                ]);
            }
        }
    }
}
