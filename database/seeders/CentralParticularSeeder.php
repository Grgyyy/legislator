<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CentralParticularSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch the ID of the region where name is 'NCR'
        $region = DB::table('regions')
            ->where('name', 'NCR')
            ->first();
        
        if (!$region) {
            // Handle the case where the region is not found
            echo "Region 'NCR' not found.";
            return;
        }
        
        $region_id = $region->id;

        // Fetch the ID of the province where name is 'Not Applicable' and belongs to the fetched region
        $province = DB::table('provinces')
            ->where('name', 'Not Applicable')
            ->where('region_id', $region_id)
            ->first();
        
        if (!$province) {
            // Handle the case where the province is not found
            echo "Province 'Not Applicable' in region 'NCR' not found.";
            return;
        }

        $province_id = $province->id;

        // Fetch the ID of the municipality where name is 'Not Applicable' and belongs to the fetched province
        $municipality = DB::table('municipalities')
            ->where('name', 'Not Applicable')
            ->where('province_id', $province_id)
            ->first();
        
        if (!$municipality) {
            // Handle the case where the municipality is not found
            echo "Municipality 'Not Applicable' in province 'Not Applicable' not found.";
            return;
        }

        $municipality_id = $municipality->id;

        // Fetch the ID of the district where name is 'Not Applicable' and belongs to the fetched municipality
        $district = DB::table('districts')
            ->where('name', 'Not Applicable')
            ->where('municipality_id', $municipality_id)
            ->first();
        
        if (!$district) {
            // Handle the case where the district is not found
            echo "District 'Not Applicable' in municipality 'Not Applicable' not found.";
            return;
        }

        $district_id = $district->id;

        // Check if a 'Central Office' record already exists for the fetched district
        $regionalOfficeExists = DB::table('particulars')
            ->where('name', 'Central Office')
            ->where('district_id', $district_id)
            ->exists();

        if (!$regionalOfficeExists) {
            // Insert a new record if it does not exist
            DB::table('particulars')->insert([
                [
                    'name' => 'Central Office',
                    'district_id' => $district_id,
                ]
            ]);
        }
    }
}
