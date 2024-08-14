<?php

namespace Database\Seeders;

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
            // National Capital Region (NCR)
            ['CaMaNaVa', 1],
            ['MuntiParLasTapat', 1],
            ['PaMaMariSan', 1],
            ['Quezon City', 1],
            ['Manila City', 1],

            // Cordillera Administrative Region (CAR)
            ['Abra', 2],
            ['Apayao', 2],
            ['Benguet', 2],
            ['Ifugao', 2],
            ['Kalinga', 2],
            ['Mountain Province', 2],

            // Region I: Ilocos Region
            ['Ilocos Norte', 3],
            ['Ilocos Sur', 3],
            ['La Union', 3],
            ['Pangasinan', 3],

            // Region II: Cagayan Valley
            ['Batanes', 4],
            ['Cagayan', 4],
            ['Isabela', 4],
            ['Nueva Vizcaya', 4],
            ['Quirino', 4],

            // Region III: Central Luzon
            ['Aurora', 5],
            ['Bataan', 5],
            ['Bulacan', 5],
            ['Nueva Ecija', 5],
            ['Pampanga', 5],
            ['Tarlac', 5],
            ['Zambales', 5],

            // Region IV-A: CALABARZON
            ['Batangas', 6],
            ['Cavite', 6],
            ['Laguna', 6],
            ['Quezon', 6],
            ['Rizal', 6],

            // Region IV-B: MIMAROPA
            ['Marinduque', 7],
            ['Occidental Mindoro', 7],
            ['Oriental Mindoro', 7],
            ['Palawan', 7],
            ['Romblon', 7],

            // Region V: Bicol Region
            ['Albay', 8],
            ['Camarines Norte', 8],
            ['Camarines Sur', 8],
            ['Catanduanes', 8],
            ['Masbate', 8],
            ['Sorsogon', 8],

            // Region VI: Western Visayas
            ['Aklan', 9],
            ['Antique', 9],
            ['Capiz', 9],
            ['Iloilo', 9],
            ['Negros Occidental', 9],

            // Region VII: Central Visayas
            ['Bohol', 10],
            ['Cebu', 10],
            ['Negros Oriental', 10],
            ['Siquijor', 10],

            // Region VIII: Eastern Visayas
            ['Biliran', 11],
            ['Eastern Samar', 11],
            ['Leyte', 11],
            ['Northern Samar', 11],
            ['Samar', 11],
            ['Southern Leyte', 11],

            // Region IX: Zamboanga Peninsula
            ['Zamboanga del Norte', 12],
            ['Zamboanga del Sur', 12],
            ['Zamboanga Sibugay', 12],

            // Region X: Northern Mindanao
            ['Bukidnon', 13],
            ['Camiguin', 13],
            ['Lanao del Norte', 13],
            ['Misamis Occidental', 13],
            ['Misamis Oriental', 13],

            // Region XI: Davao Region
            ['Davao de Oro', 14],
            ['Davao del Norte', 14],
            ['Davao del Sur', 14],
            ['Davao Occidental', 14],
            ['Davao Oriental', 14],

            // Region XII: SOCCSKSARGEN
            ['Cotabato', 15],
            ['Sarangani', 15],
            ['South Cotabato', 15],
            ['Sultan Kudarat', 15],

            // Region XIII: Caraga
            ['Agusan del Norte', 16],
            ['Agusan del Sur', 16],
            ['Bislig City', 16],
            ['Surigao del Norte', 16],
            ['Surigao del Sur', 16],

            // Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)
            ['Basilan', 17],
            ['Lanao del Sur', 17],
            ['Maguindanao', 17],
            ['Sulu', 17],
            ['Tawi-Tawi', 17],
        ];

        // Insert provinces into the 'provinces' table
        foreach ($provinces as [$name, $region]) {
            DB::table('provinces')->insert([
                'name' => $name,
                'region_id' => $region,
            ]);
        }
    }
}
