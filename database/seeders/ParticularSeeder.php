<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParticularSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all district IDs where the name is 'Not Applicable'
        $districtIds = DB::table('districts')
            ->where('name', 'Not Applicable')
            ->pluck('id'); // Collect all district IDs with this name

        foreach ($districtIds as $districtId) {
            // Check if 'Regional Office' already exists for this district
            $regionalOfficeExists = DB::table('particulars')
                ->where('name', 'Regional Office')
                ->where('district_id', $districtId)
                ->exists();

            $centralOfficeExists = DB::table('particulars')
                ->where('name', 'Central Office')
                ->where('district_id', $districtId)
                ->exists();


            // Insert 'Regional Office' and 'Central Office' if 'Regional Office' does not exist
            if (!$regionalOfficeExists && !$centralOfficeExists) {
                DB::table('particulars')->insert([
                    [
                        'name' => 'Regional Office',
                        'district_id' => $districtId,
                    ],
                    [
                        'name' => 'Central Office',
                        'district_id' => $districtId,
                    ]
                ]);
            }
        }
    }
}
