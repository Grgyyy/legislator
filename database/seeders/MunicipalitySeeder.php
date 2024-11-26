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
        // Handle "Not Applicable" municipalities
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
                    'class' => 'Not Applicable',
                    'province_id' => $provinceId,
                ]);
            }
        }

        // Handle CaMaNaVa municipalities
        $camanavaProvince = DB::table('provinces')
            ->where('name', 'CaMaNaVa')
            ->first();

        if ($camanavaProvince) {
            $camanavaMunicipalities = [
                ['name' => 'Caloocan City', 'class' => '1st'],
                ['name' => 'Malabon City', 'class' => '1st'],
                ['name' => 'Navotas City', 'class' => '1st'],
                ['name' => 'Valenzuela City', 'class' => '1st']
            ];

            foreach ($camanavaMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $camanavaProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'name' => $municipality['name'],
                        'class' => $municipality['class'], 
                        'province_id' => $camanavaProvince->id,
                    ]);
                }
            }
        }

        // Handle MuntiParLasTaPat municipalities
        $muntiparlastapatProvince = DB::table('provinces')
            ->where('name', 'MuntiParLasTaPat')
            ->first();

        if ($muntiparlastapatProvince) {
            $muntiparlastapatMunicipalities = [
                ['name' => 'Muntinlupa City', 'class' => '1st'],
                ['name' => 'Parañaque City', 'class' => '1st'],
                ['name' => 'Las Piñas City', 'class' => '1st'],
                ['name' => 'Taguig City', 'class' => '1st'],
                ['name' => 'Pateros City', 'class' => '1st']
            ];

            foreach ($muntiparlastapatMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $muntiparlastapatProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'name' => $municipality['name'],
                        'class' => $municipality['class'], 
                        'province_id' => $muntiparlastapatProvince->id,
                    ]);
                }
            }
        }

        // Handle PasMak municipalities    
        $pasmakProvince = DB::table('provinces')
        ->where('name', 'PasMak')
        ->first();

        if ($pasmakProvince) {
            $pasmakMunicipalities = [
                ['name' => 'Pasay City', 'class' => '1st'],
                ['name' => 'Makati City', 'class' => '1st'],
            ];

            foreach ($pasmakMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $pasmakProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'name' => $municipality['name'],
                        'class' => $municipality['class'], 
                        'province_id' => $pasmakProvince->id,
                    ]);
                }
            }
        }

        // Handle PaMaMariSan municipalities    
        $pamamarisanProvince = DB::table('provinces')
        ->where('name', 'PaMaMariSan')
        ->first();

        if ($pamamarisanProvince) {
            $pamamarisanMunicipalities = [
                ['name' => 'Pasig City', 'class' => '1st'],
                ['name' => 'Mandaluyong City', 'class' => '1st'],
                ['name' => 'Marikina City', 'class' => '1st'],
                ['name' => 'San Juan City', 'class' => '1st'],
            ];

            foreach ($pamamarisanMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $pamamarisanProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'name' => $municipality['name'],
                        'class' => $municipality['class'], 
                        'province_id' => $pamamarisanProvince->id,
                    ]);
                }
            }
        }


        // Handle PaMaMariSan municipalities    
        $manilaProvince = DB::table('provinces')
        ->where('name', 'City of Manila')
        ->first();

        if ($manilaProvince) {
            $manilaMunicipalities = [
                ['name' => 'Manila City', 'class' => '1st'],
            ];

            foreach ($manilaMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $manilaProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'name' => $municipality['name'],
                        'class' => $municipality['class'], 
                        'province_id' => $manilaProvince->id,
                    ]);
                }
            }
        }

        // Handle PaMaMariSan municipalities    
        $quezoncityProvince = DB::table('provinces')
        ->where('name', 'Quezon City')
        ->first();

        if ($quezoncityProvince) {
            $quezoncityMunicipalities = [
                ['name' => 'Quezon City', 'class' => '1st'],
            ];

            foreach ($quezoncityMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $quezoncityProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'name' => $municipality['name'],
                        'class' => $municipality['class'], 
                        'province_id' => $quezoncityProvince->id,
                    ]);
                }
            }
        }
        
        
    }
}
