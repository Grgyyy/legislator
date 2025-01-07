<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $learningModes = [
            ['name' => 'Face-to-Face', 'deliver_mode_id' => 1],
            ['name' => 'Online Learning', 'deliver_mode_id' => 1],
            ['name' => 'Blended Learning', 'deliver_mode_id' => 1],
            ['name' => 'Distance Learning', 'deliver_mode_id' => 1],
            ['name' => 'Combination of Distance Learning and Face-to-Face Learning', 'deliver_mode_id' => 1],

            ['name' => 'Apprenticeship', 'deliver_mode_id' => 2],
            ['name' => 'Learnership', 'deliver_mode_id' => 2],
            ['name' => 'Program on Accelerating Farm School Establishment (PAFSE)', 'deliver_mode_id' => 2],
            ['name' => 'Industry-based/In-company training for its employees', 'deliver_mode_id' => 2],
            ['name' => 'Supervised Industry Learning (SIL)', 'deliver_mode_id' => 2],
            ['name' => 'Trainings conducted in farm schools/enterprises', 'deliver_mode_id' => 2],

            ['name' => 'Mobile Training Program of TVIs', 'deliver_mode_id' => 3],
            ['name' => 'Extension programs of TTIs', 'deliver_mode_id' => 3],
            ['name' => 'Project-based training programs conducted by TESDA PPTCs', 'deliver_mode_id' => 3],
            ['name' => 'LGU-oriented community-based program', 'deliver_mode_id' => 3],
            ['name' => "Livelihood and skills training programs run by other entities, such as NGOs, People's Organization, CSR, etc", 'deliver_mode_id' => 3],
        ];

        foreach ($learningModes as $mode) {
            // Insert or fetch the learning mode
            $learningMode = DB::table('learning_modes')->updateOrInsert(
                ['name' => $mode['name']],
                ['name' => $mode['name']]
            );

            // Fetch the learning mode record to get its ID
            $learningModeId = DB::table('learning_modes')
                ->where('name', $mode['name'])
                ->value('id');

            // Insert the relationship into the pivot table
            DB::table('delivery_learnings')->updateOrInsert([
                'delivery_mode_id' => $mode['deliver_mode_id'],
                'learning_mode_id' => $learningModeId,
            ]);
        }
    }
}
