<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define provinces with their corresponding region codes
        $provinces = [

            ['0128', 'Ilocos Norte'],
            ['0129', 'Ilocos Sur'],
            ['0133', 'La Union'],
            ['0155', 'Pangasinan'],
            ['0100', 'Not Applicable'],

            ['0209', 'Batanes'],
            ['0215', 'Cagayan'],
            ['0231', 'Isabela'],
            ['0250', 'Nueva Vizcaya'],
            ['0257', 'Quirino'],
            ['0200', 'Not Applicable'],

            ['0308', 'Bataan'],
            ['0314', 'Bulacan'],
            ['0330', 'Pampanga'],
            ['0331', 'Zambales'],
            ['0349', 'Nueva Ecija'],
            ['0354', 'Pampanga'],
            ['0369', 'Tarlac'],
            ['0371', 'Zambales'],
            ['0377', 'Aurora'],
            ['0300', 'Not Applicable'],

            ['0410	', 'Batangas'],
            ['0421', 'Cavite'],
            ['0434', 'Laguna'],
            ['0456', 'Quezon'],
            ['0458', 'Rizal'],
            ['0400', 'Not Applicable'],

            ['0505', 'Albay'],
            ['0516', 'Camarines Norte'],
            ['0517', 'Camarines Sur'],
            ['0520', 'Catanduanes'],
            ['0541', 'Masbate'],
            ['0562', 'Sorsogon'],
            ['0500', 'Not Applicable'],

            ['0604', 'Aklan'],
            ['0606', 'Antique'],
            ['0619', 'Capiz'],
            ['0630', 'Iloilo'],
            ['0645', 'Negros Occidental	'],
            ['0679', 'Guimaras'],
            ['0600', 'Not Applicable'],


            ['0712', 'Bohol'],
            ['0722', 'Cebu'],
            ['0746', 'Negros Oriental'],
            ['0761', 'Siquijor'],
            ['0700', 'Not Applicable'],

            ['0826', 'Eastern Samar'],
            ['0837', 'Leyte'],
            ['0848', 'Northern Samar'],
            ['0860', 'Samar (Western Samar)'],
            ['0864	', 'Southern Leyte'],
            ['0878', 'Biliran'],
            ['0800', 'Not Applicable'],

            ['0972', 'Zamboanga del Norte'],
            ['0973', 'Zamboanga del Sur'],
            ['0983', 'Zamboanga Sibugay'],
            ['0997', 'City of Isabela'],
            ['0900', 'Not Applicable'],

            ['1013', 'Bukidnon'],
            ['1018', 'Camiguin'],
            ['1035', 'Lanao del Norte'],
            ['1042', 'Misamis Occidental'],
            ['1043', 'Misamis Oriental'],
            ['1000', 'Not Applicable'],


            ['1123', 'Davao del Norte'],
            ['1124', 'Davao del Sur'],
            ['1125', 'Davao Oriental'],
            ['1182', 'Davao de Oro'],
            ['1186', 'Davao Occidental'],
            ['1100', 'Not Applicable'],

            ['1247', 'North Cotabato'],
            ['1265', 'South Cotabato'],
            ['1265', 'Sultan Kudarat'],
            ['1280', 'Sarangani'],
            ['1298', 'Cotabato City'],
            ['1200', 'Not Applicable'],

            ['1300', 'CaMaNaVa'],
            ['1300', 'MuntiParLasTaPat'],
            ['1300', 'PasMak'],
            ['1300', 'PaMaMariSan'],
            ['1300', 'Manila City'],
            ['1300', 'Quezon City'],
            ['1300', 'Not Applicable'],

            ['1401', 'Abra'],
            ['1411', 'Benguet'],
            ['1427', 'Ifugao'],
            ['1432', 'Kalinga'],
            ['1444', 'Mountain Province	'],
            ['1481', 'Apayao'],
            ['1400', 'Not Applicable'],

            ['1602', 'Agusan del Norte'],
            ['1603', 'Agusan del Sur'],
            ['1667', 'Surigao del Norte'],
            ['1668', 'Surigao del Sur'],
            ['1685', 'Dinagat Islands'],
            ['1600', 'Not Applicable'],

            ['1740', 'Marinduque'],
            ['1751', 'Occidental Mindoro'],
            ['1752', 'Oriental Mindoro'],
            ['1753', 'Palawan'],
            ['1759', 'Romblon'],
            ['1700', 'Not Applicable'],

            ['1845', 'Negros Occidental'],
            ['1846', 'Negros Oriental'],
            ['1861', 'Siquijor'],
            ['1800', 'Not Applicable'],

            ['1907', 'Basilan'],
            ['1936', 'Lanao del Sur'],
            ['1966', 'Sulu'],
            ['1970', 'Tawi-tawi'],
            ['1987', 'Maguindanao del Norte'],
            ['1988', 'Maguindanao del Sur'],
            ['1999', 'Interim Province Code for 63 barangays of BARMM based on PSA Board Resolution No. 13 Series of 2021'],
            ['1900', 'Not Applicable'],

            ['0000', 'Not Applicable'],

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
