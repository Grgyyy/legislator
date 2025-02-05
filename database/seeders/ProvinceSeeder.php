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
            ['13000', 'CaMaNaVa'],
            ['13000', 'Manila City'],
            ['13000', 'MuntiParLasTaPat'],
            ['13000', 'PaMaMariSan'],
            ['13000', 'PasMak'],
            ['13000', 'Quezon City'],
            ['13000', 'Not Applicable'],

            ['14001', 'Abra'],
            ['14011', 'Benguet'],
            ['14027', 'Ifugao'],
            ['14032', 'Kalinga'],
            ['14044', 'Mountain Province'],
            ['14081', 'Apayao'],
            ['14000', 'Not Applicable'],

            ['01028', 'Ilocos Norte'],
            ['01029', 'Ilocos Sur'],
            ['01033', 'La Union'],
            ['01055', 'Pangasinan'],
            ['01000', 'Not Applicable'],

            ['02009', 'Batanes'],
            ['02015', 'Cagayan'],
            ['02031', 'Isabela'],
            ['02050', 'Nueva Vizcaya'],
            ['02057', 'Quirino'],
            ['02000', 'Not Applicable'],

            ['03008', 'Bataan'],
            ['03014', 'Bulacan'],
            ['03049', 'Nueva Ecija'],
            ['03054', 'Pampanga'],
            ['03069', 'Tarlac'],
            ['03071', 'Zambales'],
            ['03077', 'Aurora'],
            ['03000', 'Not Applicable'],

            ['04010	', 'Batangas'],
            ['04021', 'Cavite'],
            ['04034', 'Laguna'],
            ['04056', 'Quezon'],
            ['04058', 'Rizal'],
            ['04000', 'Not Applicable'],

            ['17040', 'Marinduque'],
            ['17051', 'Occidental Mindoro'],
            ['17052', 'Oriental Mindoro'],
            ['17053', 'Palawan'],
            ['17059', 'Romblon'],
            ['17000', 'Not Applicable'],

            ['05005', 'Albay'],
            ['05016', 'Camarines Norte'],
            ['05017', 'Camarines Sur'],
            ['05020', 'Catanduanes'],
            ['05041', 'Masbate'],
            ['05062', 'Sorsogon'],
            ['05000', 'Not Applicable'],

            ['06004', 'Aklan'],
            ['06006', 'Antique'],
            ['06019', 'Capiz'],
            ['06030', 'Iloilo'],
            ['06079', 'Guimaras'],
            ['06000', 'Not Applicable'],

            ['18045', 'Negros Occidental'],
            ['18046', 'Negros Oriental'],
            ['18061', 'Siquijor'],
            ['18000', 'Not Applicable'],

            ['07012', 'Bohol'],
            ['07022', 'Cebu'],
            ['07000', 'Not Applicable'],

            ['08026', 'Eastern Samar'],
            ['08037', 'Leyte'],
            ['08048', 'Northern Samar'],
            ['08060', 'Samar'],
            ['08064', 'Southern Leyte'],
            ['08078', 'Biliran'],
            ['08000', 'Not Applicable'],

            ['09072', 'Zamboanga del Norte'],
            ['09073', 'Zamboanga del Sur'],
            ['09083', 'Zamboanga Sibugay'],
            ['09000', 'Not Applicable'],

            ['10013', 'Bukidnon'],
            ['10018', 'Camiguin'],
            ['10035', 'Lanao del Norte'],
            ['10042', 'Misamis Occidental'],
            ['10043', 'Misamis Oriental'],
            ['10000', 'Not Applicable'],

            ['11023', 'Davao del Norte'],
            ['11024', 'Davao del Sur'],
            ['11025', 'Davao Oriental'],
            ['11082', 'Davao de Oro'],
            ['11086', 'Davao Occidental'],
            ['11000', 'Not Applicable'],

            ['12047', 'Cotabato'],
            ['12063', 'South Cotabato'],
            ['12065', 'Sultan Kudarat'],
            ['12080', 'Sarangani'],
            ['12000', 'Not Applicable'],

            ['16002', 'Agusan del Norte'],
            ['16003', 'Agusan del Sur'],
            ['16067', 'Surigao del Norte'],
            ['16068', 'Surigao del Sur'],
            ['16085', 'Dinagat Islands'],
            ['16000', 'Not Applicable'],

            ['19007', 'Basilan'],
            ['19036', 'Lanao del Sur'],
            ['19066', 'Sulu'],
            ['19070', 'Tawi-tawi'],
            ['19087', 'Maguindanao del Norte'],
            ['19088', 'Maguindanao del Sur'],
            ['19000', 'Not Applicable'],

            ['00000', 'Not Applicable'],
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