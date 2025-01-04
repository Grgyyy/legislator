<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Models\Priority;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectProposal extends CreateRecord
{
    protected static string $resource = ProjectProposalResource::class;

    public function getBreadcrumbs(): array
    {

        return [
            route('filament.admin.resources.project-proposals.index') => 'Project Proposal Programs',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected ?string $heading = 'Create Project Proposal Program';

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $projectProposalProgram = strtolower($data['program_name']);

            // Check if the program already exists
            $projectProposalProgramExist = TrainingProgram::where('title', $projectProposalProgram)->exists();

            if ($projectProposalProgramExist) {
                throw new \Exception("Program with name '{$data['program_name']}' already exists.");
            }

            // Retrieve default sectors
            $tvetSector = Tvet::where('name', 'Not Applicable')->first();
            $prioSector = Priority::where('name', 'Not Applicable')->first();

            // Create the training program
            $trainingProgramRecord = TrainingProgram::create([
                'title' => $projectProposalProgram,
                'tvet_id' => $tvetSector->id,
                'priority_id' => $prioSector->id,
            ]);

            // Fetch all scholarship programs
            $scholarshipPrograms = ScholarshipProgram::all();

            // Attach scholarship programs to the training program
            $trainingProgramRecord->scholarshipPrograms()->syncWithoutDetaching(
                $scholarshipPrograms->pluck('id')->toArray()
            );

            // Create qualifications for all scholarship programs
            foreach ($scholarshipPrograms as $scholarshipProgram) {
                QualificationTitle::create([
                    'training_program_id' => $trainingProgramRecord->id,
                    'scholarship_program_id' => $scholarshipProgram->id,
                    'soc' => 0, // Replace with actual status logic if needed
                ]);
            }

            // Return the main record being created (e.g., TrainingProgram)
            return $trainingProgramRecord;
        });
    }


}
