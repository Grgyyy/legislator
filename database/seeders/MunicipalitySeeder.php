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
        for ($i = 1; $i < 104; $i++) {
            $municipality = ['Not Applicable', $i];
            $municpalityExist = DB::table('municipalities')
                    ->where('name', $municipality[0])
                    ->where('province_id', $i)
                    ->exists();

            if (!$municpalityExist) {
                        DB::table('municipalities')->insert([
                            'name' => $municipality[0],
                            'province_id' => $municipality[1],
                        ]);
            }
        }
    }
}
