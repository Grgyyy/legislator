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
            ['code' => 'TWSP', 'name' => 'TWSP', 'desc' => 'Training for Work Scholarship'],
            ['code' => 'STEP', 'name' => 'STEP', 'desc' => 'Special Training for Employment Program'],
            ['code' => 'TTSP', 'name' => 'TTSP', 'desc' => 'Tulong Trabaho Scholarship Program'],
            ['code' => 'CFSP', 'name' => 'CFSP', 'desc' => 'Coconut Farmers Scholarships Program'],
            ['code' => 'PESF', 'name' => 'PESFA', 'desc' => 'Private Education Student Fund Assistance'],
            ['code' => 'RESP', 'name' => 'RESP', 'desc' => 'Rice Extension Services Program'],
            ['code' => 'UAQD', 'name' => 'UAQTEA', 'desc' => 'Universal Access to Quality Tertiary Education Act'],
            ['code' => 'DOTR', 'name' => 'TSUPER', 'desc' => 'Tsuper Iskolar'],
        ];

        foreach ($programs as $program) {
            DB::table('scholarship_programs')->updateOrInsert($program);
        }

    }
}
