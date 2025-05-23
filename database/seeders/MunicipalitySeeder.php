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
                    'class' => 'Not Applicable',
                    'province_id' => $provinceId,
                ]);
            }
        }

        $camanavaProvince = DB::table('provinces')
            ->where('name', 'CaMaNaVa')
            ->first();

        if ($camanavaProvince) {
            $camanavaMunicipalities = [
                ['code' => '1380100000', 'name' => 'Caloocan City', 'class' => '1st'],
                ['code' => '1380400000', 'name' => 'Malabon City', 'class' => '1st'],
                ['code' => '1380900000','name' => 'Navotas City', 'class' => '1st'],
                ['code' => '1381600000', 'name' => 'Valenzuela City', 'class' => '1st']
            ];

            foreach ($camanavaMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('code', $municipality['code'])
                    ->where('name', $municipality['name'])
                    ->where('province_id', $camanavaProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $camanavaProvince->id,
                    ]);
                }
            }
        }

        $muntiparlastapatProvince = DB::table('provinces')
            ->where('name', 'MuntiParLasTaPat')
            ->first();

        if ($muntiparlastapatProvince) {
            $muntiparlastapatMunicipalities = [
                ['code' => '1380800000', 'name' => 'Muntinlupa City', 'class' => '1st'],
                ['code' => '1381000000', 'name' => 'Parañaque City', 'class' => '1st'],
                ['code' => '1380200000', 'name' => 'Las Piñas City', 'class' => '1st'],
                ['code' => '1381500000', 'name' => 'Taguig City', 'class' => '1st'],
                ['code' => '1381701000', 'name' => 'Pateros City', 'class' => '1st']
            ];

            foreach ($muntiparlastapatMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('code', $municipality['code'])
                    ->where('name', $municipality['name'])
                    ->where('province_id', $muntiparlastapatProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $muntiparlastapatProvince->id,
                    ]);
                }
            }
        }

        $pasmakProvince = DB::table('provinces')
            ->where('name', 'PasMak')
            ->first();

        if ($pasmakProvince) {
            $pasmakMunicipalities = [
                ['code' => '1381100000', 'name' => 'Pasay City', 'class' => '1st'],
                ['code' => '1380300000', 'name' => 'Makati City', 'class' => '1st'],
            ];

            foreach ($pasmakMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('code', $municipality['code'])
                    ->where('name', $municipality['name'])
                    ->where('province_id', $pasmakProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $pasmakProvince->id,
                    ]);
                }
            }
        }

        $pamamarisanProvince = DB::table('provinces')
            ->where('name', 'PaMaMariSan')
            ->first();

        if ($pamamarisanProvince) {
            $pamamarisanMunicipalities = [
                ['code' => '1381200000', 'name' => 'Pasig City', 'class' => '1st'],
                ['code' => '1380500000', 'name' => 'Mandaluyong City', 'class' => '1st'],
                ['code' => '1380700000', 'name' => 'Marikina City', 'class' => '1st'],
                ['code' => '1381400000', 'name' => 'San Juan City', 'class' => '1st'],
            ];

            foreach ($pamamarisanMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('code', $municipality['code'])
                    ->where('name', $municipality['name'])
                    ->where('province_id', $pamamarisanProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $pamamarisanProvince->id,
                    ]);
                }
            }
        }

        $manilaProvince = DB::table('provinces')
            ->where('name', 'Manila City')
            ->first();

        if ($manilaProvince) {
            $manilaMunicipalities = [
                ['code' => '1380600000', 'name' => 'Manila City', 'class' => '1st'],
            ];

            foreach ($manilaMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('code', $municipality['code'])
                    ->where('name', $municipality['name'])
                    ->where('province_id', $manilaProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $manilaProvince->id,
                    ]);
                }
            }
        }

        $quezoncityProvince = DB::table('provinces')
            ->where('name', 'Quezon City')
            ->first();

        if ($quezoncityProvince) {
            $quezoncityMunicipalities = [
                ['code' => '1381300000', 'name' => 'Quezon City', 'class' => '1st'],
            ];

            foreach ($quezoncityMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('code', $municipality['code'])
                    ->where('name', $municipality['name'])
                    ->where('province_id', $quezoncityProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $quezoncityProvince->id,
                    ]);
                }
            }
        }

        $bulacanProvince = DB::table('provinces')
            ->where('name', 'Bulacan')
            ->first();

        if ($bulacanProvince) {
            $bulacanMunicipalities = [
                ['code' => '0301420000', 'name' => 'City of San Jose Del Monte', 'class' => '1st'],
            ];

            foreach ($bulacanMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $bulacanProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $bulacanProvince->id,
                    ]);
                }
            }
        }

        $lagunaProvince = DB::table('provinces')
            ->where('name', 'Laguna')
            ->first();

        if ($lagunaProvince) {
            $lagunaMunicipalities = [
                ['code' => '0403403000', 'name' => 'City of Biñan', 'class' => '1st'],
                ['code' => '0403405000', 'name' => 'City of Calamba', 'class' => '1st'],
                ['code' => '0403428000', 'name' => 'City of Santa Rosa', 'class' => '1st'],
            ];

            foreach ($lagunaMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $lagunaProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $lagunaProvince->id,
                    ]);
                }
            }
        }

        $rizalProvince = DB::table('provinces')
            ->where('name', 'Rizal')
            ->first();

        if ($rizalProvince) {
            $rizalMunicipalities = [
                ['code' => '0405802000', 'name' => 'City of Antipolo', 'class' => '1st'],
            ];

            foreach ($rizalMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $rizalProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $rizalProvince->id,
                    ]);
                }
            }
        }

        $iloiloProvince = DB::table('provinces')
            ->where('name', 'Iloilo')
            ->first();

        if ($iloiloProvince) {
            $iloiloMunicipalities = [
                ['code' => '0631000000', 'name' => 'City of Iloilo', 'class' => '1st'],
            ];

            foreach ($iloiloMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $iloiloProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $iloiloProvince->id,
                    ]);
                }
            }
        }

        $negrosOccidentalProvince = DB::table('provinces')
        ->where('name', 'Negros Occidental')
        ->first();

        if ($negrosOccidentalProvince) {
            $negrosOccidentalMunicipalities = [
                ['code' => '1830200000', 'name' => 'City of Bacolod', 'class' => '1st'],
            ];

            foreach ($negrosOccidentalMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $negrosOccidentalProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $negrosOccidentalProvince->id,
                    ]);
                }
            }
        }



        $cebuProvince = DB::table('provinces')
            ->where('name', 'Cebu')
            ->first();

        if ($cebuProvince) {
            $cebuMunicipalities = [
                ['code' => '0730600000', 'name' => 'City of Cebu', 'class' => '1st'],
                ['code' => '0731100000', 'name' => 'City of Lapu-Lapu', 'class' => '1st'],
                ['code' => '0731300000', 'name' => 'City of Mandaue', 'class' => '1st'],
            ];

            foreach ($cebuMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $cebuProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $cebuProvince->id,
                    ]);
                }
            }
        }


        $zamboangaProvince = DB::table('provinces')
            ->where('name', 'Zamboanga del Sur')
            ->first();

        if ($zamboangaProvince) {
            $zamboangaMunicipalities = [
                ['code' => '0931700000', 'name' => 'City of Zamboanga', 'class' => '1st'],
            ];

            foreach ($zamboangaMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $zamboangaProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $zamboangaProvince->id,
                    ]);
                }
            }
        }


        $zamboangaSibugayProvince = DB::table('provinces')
            ->where('name', 'Basilan')
            ->first();

        if ($zamboangaSibugayProvince) {
            $zamboangaSibugayMunicipalities = [
                ['code' => '0990101000', 'name' => 'City of Isabela', 'class' => '1st'],
            ];

            foreach ($zamboangaSibugayMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $zamboangaSibugayProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $zamboangaSibugayProvince->id,
                    ]);
                }
            }
        }

        $lanaoProvince = DB::table('provinces')
            ->where('name', 'Lanao del Norte')
            ->first();

        if ($lanaoProvince) {
            $lanaoMunicipalities = [
                ['code' => '1030900000', 'name' => 'City of Iligan', 'class' => '1st'],
            ];

            foreach ($lanaoMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $lanaoProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $lanaoProvince->id,
                    ]);
                }
            }
        }

        $misamisOrientalProvince = DB::table('provinces')
            ->where('name', 'Misamis Oriental')
            ->first();

        if ($misamisOrientalProvince) {
            $misamisOrientalMunicipalities = [
                ['code' => '1030500000', 'name' => 'City of Cagayan De Oro', 'class' => '1st'],
            ];

            foreach ($misamisOrientalMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $misamisOrientalProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $misamisOrientalProvince->id,
                    ]);
                }
            }
        }


        $davaoDelSurProvince = DB::table('provinces')
            ->where('name', 'Davao del Sur')
            ->first();

        if ($davaoDelSurProvince) {
            $davaoDelSurMunicipalities = [
                ['code' => '1130700000', 'name' => 'City of Davao', 'class' => '1st'],
            ];

            foreach ($davaoDelSurMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $davaoDelSurProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $davaoDelSurProvince->id,
                    ]);
                }
            }
        }

        $southCotabatoProvince = DB::table('provinces')
            ->where('name', 'Sarangani')
            ->first();

        if ($southCotabatoProvince) {
            $southCotabatoMunicipalities = [
                ['code' => '1230800000', 'name' => 'City of General Santos', 'class' => '1st'],
            ];

            foreach ($southCotabatoMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $southCotabatoProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $southCotabatoProvince->id,
                    ]);
                }
            }
        }


        $agusanDelNorteProvince = DB::table('provinces')
            ->where('name', 'Agusan del Norte')
            ->first();

        if ($agusanDelNorteProvince) {
            $agusanDelNorteMunicipalities = [
                ['code' => '1630400000', 'name' => 'City of Butuan', 'class' => '1st'],
            ];

            foreach ($agusanDelNorteMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $agusanDelNorteProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $agusanDelNorteProvince->id,
                    ]);
                }
            }
        }


        $benguetProvince = DB::table('provinces')
            ->where('name', 'Benguet')
            ->first();

        if ($benguetProvince) {
            $benguetMunicipalities = [
                ['code' => '1430300000', 'name' => 'City of Baguio', 'class' => '1st'],
            ];

            foreach ($benguetMunicipalities as $municipality) {
                $municipalityExists = DB::table('municipalities')
                    ->where('name', $municipality['name'])
                    ->where('province_id', $benguetProvince->id)
                    ->exists();

                if (!$municipalityExists) {
                    DB::table('municipalities')->insert([
                        'code' => $municipality['code'],
                        'name' => $municipality['name'],
                        'class' => $municipality['class'],
                        'province_id' => $benguetProvince->id,
                    ]);
                }
            }
        }

    }
}
