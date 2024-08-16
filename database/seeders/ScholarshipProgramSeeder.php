<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ScholarshipProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            ['code' => '1', 'name' => 'TWSP', 'desc' => 'TWSP'],
            ['code' => '2', 'name' => 'STEP', 'desc' => 'STEP'],
            ['code' => '3', 'name' => 'TTSP', 'desc' => 'TTSP'],
            ['code' => '3', 'name' => 'CFSP', 'desc' => 'CFSP'],
            ['code' => '3', 'name' => 'PESFA', 'desc' => 'PESFA'],
            ['code' => '3', 'name' => 'RESP', 'desc' => 'RESP'],
            ['code' => '3', 'name' => 'UAQTEA', 'desc' => 'UAQTEA'],
        ];

        foreach ($programs as $program) {
            DB::table('scholarship_programs')->updateOrInsert($program);
        }

    }
}
