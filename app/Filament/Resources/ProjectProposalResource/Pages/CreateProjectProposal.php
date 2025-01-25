<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Models\Priority;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProjectProposal extends CreateRecord
{
    protected static string $resource = ProjectProposalResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.project-proposals.index') => 'Project Proposal Programs',
            'Create',
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected ?string $heading = 'Create Project Proposal Program';

    public function disabledSoc(): bool
    {
        return false;
    }

    public function noQualiCode(): bool
    {
        return true;
    }

    public function noSocCode(): bool
    {
        return true;
    }

    public function noSchoPro(): bool
    {
        return false;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $trainingProgram = TrainingProgram::withTrashed()
                ->where(DB::raw('LOWER(title)'), strtolower($data['title']))
                ->where('tvet_id', $data['tvet_id'])
                ->where('priority_id', $data['priority_id'])
                ->first();

            if ($trainingProgram) {
                NotificationHandler::handleValidationException(
                    'Training Program Exists',
                    "The Training Program '{$trainingProgram->title}' already exists and cannot be added to the program proposal."
                );
            }

            // Get the last record where soc_code starts with 'PROP'
            $lastTrainingProgram = TrainingProgram::where('soc_code', 'like', 'PROP%')
                ->orderByDesc('soc_code') // Get the most recent soc_code
                ->first();

            // Extract the number part from the soc_code, remove 'PROP' and leading zeros
            $lastSocCode = $lastTrainingProgram ? substr($lastTrainingProgram->soc_code, 4) : 0;
            $lastSocCode = ltrim($lastSocCode, '0'); // Remove leading zeros
            $newSocCode = (int)$lastSocCode + 1; // Increment by 1

            // Generate the formatted SOC code for the new record
            $formattedSocCode = $this->formatSocCode($newSocCode);

            // Create the project proposal program
            $projectProposalProgram = TrainingProgram::create([
                'soc_code'   => $formattedSocCode,
                'title'      => $data['title'],
                'priority_id' => $data['priority_id'],
                'tvet_id'    => $data['tvet_id'],
                'soc' => 0
            ]);

            if (!empty($data['scholarshipPrograms'])) {
                $projectProposalProgram->scholarshipPrograms()->sync($data['scholarshipPrograms']);

                foreach ($data['scholarshipPrograms'] as $scholarshipProgramId) {
                    $scholarshipProgram = ScholarshipProgram::find($scholarshipProgramId);

                    QualificationTitle::create([
                        'training_program_id' => $projectProposalProgram->id,
                        'scholarship_program_id' => $scholarshipProgram->id,
                        'status_id' => 1,
                        'soc' => 0
                    ]);
                }
            }

            NotificationHandler::sendSuccessNotification('Created', 'The training program has been created successfully.');

            return $projectProposalProgram;
        });
    }


    private function formatSocCode($currentSocCode)
    {
        if ($currentSocCode > 99999) {
            return 'PROP' . $currentSocCode;
        }

        return sprintf('PROP%06d', $currentSocCode);
    }
}
