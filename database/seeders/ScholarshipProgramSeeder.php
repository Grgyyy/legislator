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
            ['code' => '1', 'name' => 'TWSP', 'desc' => 'Training for Work Scholarship'],
            ['code' => '2', 'name' => 'STEP', 'desc' => 'Special Training for Employment Program'],
            ['code' => '3', 'name' => 'TTSP', 'desc' => 'Tulong Trabaho Scholarship Program'],
            ['code' => '3', 'name' => 'CFSP', 'desc' => 'Coconut Farmers Scholarships Program'],
            ['code' => '3', 'name' => 'PESFA', 'desc' => 'Private Education Student Fund Assistance'],
            ['code' => '3', 'name' => 'RESP', 'desc' => 'Rice Extension Services Program'],
            ['code' => '3', 'name' => 'UAQTEA', 'desc' => 'Universal Access to Quality Tertiary Education Act'],
            ['code' => '3', 'name' => 'BKSTP', 'desc' => 'Barangay Kabuhayan Skills Training Program'],
        ];

        foreach ($programs as $program) {
            DB::table('scholarship_programs')->updateOrInsert($program);
        }

    }
}
