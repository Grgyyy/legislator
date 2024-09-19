<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionalParticularSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $districtIds = DB::table('districts')
            ->where('name', 'Not Applicable')
            ->pluck('id');

        foreach ($districtIds as $districtId) {
            $regionalOfficeExists = DB::table('particulars')
                ->where('name', 'Regional Office')
                ->where('district_id', $districtId)
                ->exists();

            if (!$regionalOfficeExists) {
                DB::table('particulars')->insert([
                    [
                        'name' => 'Regional Office',
                        'district_id' => $districtId,
                    ]
                ]);
            }
        }
    }
}
