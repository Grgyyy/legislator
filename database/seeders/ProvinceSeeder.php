<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinces = [
            ['1300000000', 'CaMaNaVa'],
            ['1300000000', 'Manila City'],
            ['1300000000', 'MuntiParLasTaPat'],
            ['1300000000', 'PaMaMariSan'],
            ['1300000000', 'PasMak'],
            ['1300000000', 'Quezon City'],
            ['1300000000', 'Not Applicable'],

            ['1400100000', 'Abra'],
            ['1401100000', 'Benguet'],
            ['1402700000', 'Ifugao'],
            ['1403200000', 'Kalinga'],
            ['1404400000', 'Mountain Province'],
            ['1408100000', 'Apayao'],
            ['1400000000', 'Not Applicable'],

            ['0102800000', 'Ilocos Norte'],
            ['0102900000', 'Ilocos Sur'],
            ['0103300000', 'La Union'],
            ['0105500000', 'Pangasinan'],
            ['0100000000', 'Not Applicable'],

            ['0200900000', 'Batanes'],
            ['0201500000', 'Cagayan'],
            ['0203100000', 'Isabela'],
            ['0205000000', 'Nueva Vizcaya'],
            ['0205700000', 'Quirino'],
            ['0200000000', 'Not Applicable'],

            ['0300800000', 'Bataan'],
            ['0301400000', 'Bulacan'],
            ['0304900000', 'Nueva Ecija'],
            ['0305400000', 'Pampanga'],
            ['0306900000', 'Tarlac'],
            ['0307100000', 'Zambales'],
            ['0307700000', 'Aurora'],
            ['0300000000', 'Not Applicable'],

            ['0401000000', 'Batangas'],
            ['0402100000', 'Cavite'],
            ['0403400000', 'Laguna'],
            ['0405600000', 'Quezon'],
            ['0405800000', 'Rizal'],
            ['0400000000', 'Not Applicable'],

            ['1704000000', 'Marinduque'],
            ['1705100000', 'Occidental Mindoro'],
            ['1705200000', 'Oriental Mindoro'],
            ['1705300000', 'Palawan'],
            ['1705900000', 'Romblon'],
            ['1700000000', 'Not Applicable'],

            ['0500500000', 'Albay'],
            ['0501600000', 'Camarines Norte'],
            ['0501700000', 'Camarines Sur'],
            ['0502000000', 'Catanduanes'],
            ['0504100000', 'Masbate'],
            ['0506200000', 'Sorsogon'],
            ['0500000000', 'Not Applicable'],

            ['0600400000', 'Aklan'],
            ['0600600000', 'Antique'],
            ['0601900000', 'Capiz'],
            ['0603000000', 'Iloilo'],
            ['0607900000', 'Guimaras'],
            ['0600000000', 'Not Applicable'],

            ['1804500000', 'Negros Occidental'],
            ['1804600000', 'Negros Oriental'],
            ['1806100000', 'Siquijor'],
            ['1800000000', 'Not Applicable'],

            ['0701200000', 'Bohol'],
            ['0702200000', 'Cebu'],
            ['0700000000', 'Not Applicable'],

            ['0802600000', 'Eastern Samar'],
            ['0803700000', 'Leyte'],
            ['0804800000', 'Northern Samar'],
            ['0806000000', 'Samar'],
            ['0806400000', 'Southern Leyte'],
            ['0807800000', 'Biliran'],
            ['0800000000', 'Not Applicable'],

            ['0907200000', 'Zamboanga del Norte'],
            ['0907300000', 'Zamboanga del Sur'],
            ['0908300000', 'Zamboanga Sibugay'],
            ['0900000000', 'Not Applicable'],

            ['1001300000', 'Bukidnon'],
            ['1001800000', 'Camiguin'],
            ['1003500000', 'Lanao del Norte'],
            ['1004200000', 'Misamis Occidental'],
            ['1004300000', 'Misamis Oriental'],
            ['1000000000', 'Not Applicable'],

            ['1102300000', 'Davao del Norte'],
            ['1102400000', 'Davao del Sur'],
            ['1102500000', 'Davao Oriental'],
            ['1108200000', 'Davao de Oro'],
            ['1108600000', 'Davao Occidental'],
            ['1100000000', 'Not Applicable'],

            ['1204700000', 'Cotabato'],
            ['1206300000', 'South Cotabato'],
            ['1206500000', 'Sultan Kudarat'],
            ['1208000000', 'Sarangani'],
            ['1200000000', 'Not Applicable'],

            ['1600200000', 'Agusan del Norte'],
            ['1600300000', 'Agusan del Sur'],
            ['1606700000', 'Surigao del Norte'],
            ['1606800000', 'Surigao del Sur'],
            ['1608500000', 'Dinagat Islands'],
            ['1600000000', 'Not Applicable'],

            ['1900700000', 'Basilan'],
            ['1903600000', 'Lanao del Sur'],
            ['1906600000', 'Sulu'],
            ['1907000000', 'Tawi-tawi'],
            ['1908700000', 'Maguindanao del Norte'],
            ['1908800000', 'Maguindanao del Sur'],
            ['1900000000', 'Not Applicable'],

            ['0000000000', 'Not Applicable'],
        ];

        foreach ($provinces as [$code, $name]) {
            $regionId = substr(trim($code), 0, 2);

            $region = Region::where('code', $regionId)->first();

            if ($region) {
                Province::create([
                    'code' => $code,
                    'name' => $name,
                    'region_id' => $region->id,
                ]);
            } else {
                $this->command->warn("Region not found for Province: {$name} ({$code})");
            }
        }
    }
}