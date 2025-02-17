<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = DB::table('provinces')
            ->where('name', 'Not Applicable')
            ->pluck('id');

        foreach ($provinces as $provinceId) {
            $districtExists = DB::table('districts')
                ->where('name', 'Not Applicable')
                ->where('province_id', $provinceId)
                ->exists();

            if (!$districtExists) {
                $districtId = DB::table('districts')->insertGetId([
                    'name' => 'Not Applicable',
                    'province_id' => $provinceId,
                ]);

                $municipalityId = DB::table('municipalities')
                    ->where('name', 'Not Applicable')
                    ->where('province_id', $provinceId)
                    ->value('id');

                if ($municipalityId) {
                    DB::table('district_municipalities')->insert([
                        'district_id' => $districtId,
                        'municipality_id' => $municipalityId,
                    ]);
                }
            }
        }

        $ncrMunicipalities = [
            ['name' => 'Caloocan City', 'districts' => 3],
            ['name' => 'Malabon City', 'districts' => 1],
            ['name' => 'Navotas City', 'districts' => 1],
            ['name' => 'Valenzuela City', 'districts' => 2],
            ['name' => 'Muntinlupa City', 'districts' => 1],
            ['name' => 'Parañaque City', 'districts' => 2],
            ['name' => 'Las Piñas City', 'districts' => 1],
            ['name' => 'Taguig City', 'districts' => 2],
            ['name' => 'Pateros City', 'districts' => 1],
            ['name' => 'Pasay City', 'districts' => 2],
            ['name' => 'Makati City', 'districts' => 2],
            ['name' => 'Pasig City', 'districts' => 1],
            ['name' => 'Mandaluyong City', 'districts' => 1],
            ['name' => 'Marikina City', 'districts' => 2],
            ['name' => 'San Juan City', 'districts' => 1],
            ['name' => 'Manila City', 'districts' => 6],
            ['name' => 'Quezon City', 'districts' => 6],
        ];

        foreach ($ncrMunicipalities as $ncrMunicipality) {
            $municipality = DB::table('municipalities')
                ->where('name', $ncrMunicipality['name'])
                ->first();

            if ($municipality) {
                for ($i = 1; $i <= $ncrMunicipality['districts']; $i++) {
                    $districtName = $ncrMunicipality['districts'] === 1 ? 'Lone District' : "District {$i}";
                    $districtExists = DB::table('districts')
                        ->where('name', $districtName)
                        ->where('municipality_id', $municipality->id)
                        ->exists();

                    if (!$districtExists) {
                        $districtId = District::create([
                            'name' => $districtName,
                            'municipality_id' => $municipality->id,
                            'province_id' => $municipality->province_id,
                        ])->id;

                        DB::table('district_municipalities')->insert([
                            'district_id' => $districtId,
                            'municipality_id' => $municipality->id,
                        ]);
                    }
                }
            }
        }

        
        $antipoloMunicipality = DB::table('municipalities')
                ->where('name', 'City of Antipolo')
                ->first();

        if ($antipoloMunicipality) {
            for ($i = 1; $i <= 2; $i++) {
                $districtName = "District {$i}";
                $districtExists = DB::table('districts')
                    ->where('name', $districtName)
                    ->where('municipality_id', $antipoloMunicipality->id)
                    ->exists();

                if (!$districtExists) {
                    $districtId = District::create([
                        'name' => $districtName,
                        'municipality_id' => $antipoloMunicipality->id,
                        'province_id' => $antipoloMunicipality->province_id,
                    ])->id;

                    if ($i < 3) {
                        DB::table('district_municipalities')->insert([
                            'district_id' => $districtId,
                            'municipality_id' => $antipoloMunicipality->id,
                        ]);
                    }
                }
            }
        }

        $rizalId = DB::table('provinces')
            ->where('name', 'Rizal')
            ->value('id');

        for ($i = 1; $i <= 4; $i++) {
            $districtName = "District {$i}";
            $districtExists = DB::table('districts')
                ->where('name', $districtName)
                ->whereNull('municipality_id')
                ->where('province_id', $rizalId)
                ->exists();

            if (!$districtExists) {
                $districtId = District::create([
                    'name' => $districtName,
                    'province_id' => $rizalId,
                ])->id;
            }
        }



        $cebuMunicipality = DB::table('municipalities')
                ->where('name', 'City of Cebu')
                ->first();

        if ($cebuMunicipality) {
            for ($i = 1; $i <= 2; $i++) {
                $districtName = "District {$i}";
                $districtExists = DB::table('districts')
                    ->where('name', $districtName)
                    ->where('municipality_id', $cebuMunicipality->id)
                    ->exists();

                if (!$districtExists) {
                    $districtId = District::create([
                        'name' => $districtName,
                        'municipality_id' => $cebuMunicipality->id,
                        'province_id' => $cebuMunicipality->province_id,
                    ])->id;

                    if ($i < 3) {
                        DB::table('district_municipalities')->insert([
                            'district_id' => $districtId,
                            'municipality_id' => $cebuMunicipality->id,
                        ]);
                    }
                }
            }
        }

        $lapulapuMunicipality = DB::table('municipalities')
                ->where('name', 'City of Lapu-Lapu')
                ->first();

        if ($lapulapuMunicipality) {
                $districtName = "Lone District";
                $districtExists = DB::table('districts')
                    ->where('name', $districtName)
                    ->where('municipality_id', $lapulapuMunicipality->id)
                    ->exists();

                if (!$districtExists) {
                    $districtId = District::create([
                        'name' => $districtName,
                        'municipality_id' => $lapulapuMunicipality->id,
                        'province_id' => $lapulapuMunicipality->province_id,
                    ])->id;

                    DB::table('district_municipalities')->insert([
                        'district_id' => $districtId,
                        'municipality_id' => $lapulapuMunicipality->id,
                    ]);
            
                }
        }

        $madaueMunicipality = DB::table('municipalities')
                ->where('name', 'City of Mandaue')
                ->first();

        if ($madaueMunicipality) {
                $districtName = "Lone District";
                $districtExists = DB::table('districts')
                    ->where('name', $districtName)
                    ->where('municipality_id', $madaueMunicipality->id)
                    ->exists();

                if (!$districtExists) {
                    $districtId = District::create([
                        'name' => $districtName,
                        'municipality_id' => $madaueMunicipality->id,
                        'province_id' => $madaueMunicipality->province_id,
                    ])->id;

                    DB::table('district_municipalities')->insert([
                        'district_id' => $districtId,
                        'municipality_id' => $madaueMunicipality->id,
                    ]);

                }
        }

        $cebuId = DB::table('provinces')
            ->where('name', 'Cebu')
            ->value('id');

        for ($i = 1; $i <= 7; $i++) {
            $districtName = "District {$i}";
            $districtExists = DB::table('districts')
                ->where('name', $districtName)
                ->whereNull('municipality_id')
                ->where('province_id', $cebuId)
                ->exists();

            if (!$districtExists) {
                $districtId = District::create([
                    'name' => $districtName,
                    'province_id' => $cebuId,
                ])->id;
            }
        }




        $zamboangaCityMunicipality = DB::table('municipalities')
                ->where('name', 'City of Zamboanga')
                ->first();

        if ($zamboangaCityMunicipality) {
            for ($i = 1; $i <= 2; $i++) {
                $districtName = "District {$i}";
                $districtExists = DB::table('districts')
                    ->where('name', $districtName)
                    ->where('municipality_id', $zamboangaCityMunicipality->id)
                    ->exists();

                if (!$districtExists) {
                    $districtId = District::create([
                        'name' => $districtName,
                        'municipality_id' => $zamboangaCityMunicipality->id,
                        'province_id' => $zamboangaCityMunicipality->province_id,
                    ])->id;

                    if ($i < 3) {
                        DB::table('district_municipalities')->insert([
                            'district_id' => $districtId,
                            'municipality_id' => $zamboangaCityMunicipality->id,
                        ]);
                    }
                }
            }
        }

        $zamboangaDelSurId = DB::table('provinces')
            ->where('name', 'Zamboanga del Sur')
            ->value('id');

        for ($i = 1; $i <= 7; $i++) {
            $districtName = "District {$i}";
            $districtExists = DB::table('districts')
                ->where('name', $districtName)
                ->whereNull('municipality_id')
                ->where('province_id', $zamboangaDelSurId)
                ->exists();

            if (!$districtExists) {
                $districtId = District::create([
                    'name' => $districtName,
                    'province_id' => $zamboangaDelSurId,
                ])->id;
            }
        }

    }
}