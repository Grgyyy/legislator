<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParticularSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i < 102; $i++) {
            $particular = ['Regional Office', $i];
            $particularExist = DB::table('particulars')
                    ->where('name', $particular[0])
                    ->where('district_id', $i)
                    ->exists();

            if (!$particularExist) {
                        DB::table('particulars')->insert([
                            'name' => $particular[0],
                            'district_id' => $particular[1],
                        ]);
            }
        }
    }
}
