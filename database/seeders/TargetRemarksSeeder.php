<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TargetRemarksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $remarks = [
            ['remarks' => 'The qualification has reached its maximum allocation within the Priority Skills of the Region, following the Area-Based Demand Driven TVET policy.'],
            [
                'remarks' => 'The qualification was not included in the priority.'
            ],
            [
                'remarks' => 'There are no registered programs based on the Compendium and SIS.'
            ],
            [
                'remarks' => 'The TVI has fully utilized its absorptive capacity.'
            ],
            [
                'remarks' => 'For the submission of the necessary documents for the re-issuance of the CTPR.'
            ],
            [
                'remarks' => 'No absorptive capacity is indicated in the Scholarship Information System.'
            ],
        ];

        foreach ($remarks as $remark) {
            DB::table('target_remarks')->updateOrInsert($remark);
        }
    }
}
