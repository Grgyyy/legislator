<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define NCR municipalities and their district counts
        $ncrMunicipalities = [
            ['name' => 'Caloocan City', 'districts' => 3],
            ['name' => 'Malabon City', 'districts' => 1],
            ['name' => 'Navotas City', 'districts' => 1],
            ['name' => 'Valenzuela City', 'districts' => 2],
            ['name' => 'Muntinlupa City', 'districts' => 1],
            ['name' => 'Parañaque City', 'districts' => 2],
            ['name' => 'Las Piñas City', 'districts' => 1],
            ['name' => 'Taguig City', 'districts' => 2],
            ['name' => 'Pateros City', 'districts' => 1], // Treated as having 1 district for seeding
            ['name' => 'Pasay City', 'districts' => 2],
            ['name' => 'Makati City', 'districts' => 2],
            ['name' => 'Pasig City', 'districts' => 1],
            ['name' => 'Mandaluyong City', 'districts' => 1],
            ['name' => 'Marikina City', 'districts' => 2],
            ['name' => 'San Juan City', 'districts' => 1],
            ['name' => 'Manila City', 'districts' => 6],
            ['name' => 'Quezon City', 'districts' => 6],
        ];

        // Insert districts for each municipality
        foreach ($ncrMunicipalities as $ncrMunicipality) {
            // Find the municipality in the database
            $municipality = DB::table('municipalities')
                ->where('name', $ncrMunicipality['name'])
                ->first();

            if ($municipality) {
                // Seed the districts for the municipality
                for ($i = 1; $i <= $ncrMunicipality['districts']; $i++) {
                    $districtName = "District {$i}";

                    // Check if the district already exists
                    $districtExists = DB::table('districts')
                        ->where('name', $districtName)
                        ->where('municipality_id', $municipality->id)
                        ->exists();

                    if (!$districtExists) {
                        $district = District::create([
                            'name' => $districtName,
                            'municipality_id' => $municipality->id,
                            'province_id' => $municipality->province_id,
                        ]);

                        $district->municipality()->attach($municipality->id);
                    }
                }
            }
        }
    }
}
