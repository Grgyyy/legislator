<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Models\Priority;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\QualificationTitle;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use DB;
use Filament\Resources\Pages\CreateRecord;

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected ?string $heading = 'Create Project Proposal Program';

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

            $projectProposalProgram = TrainingProgram::create([
                'title'       => $data['title'],
                'priority_id' => $data['priority_id'],
                'tvet_id'     => $data['tvet_id'],
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
}
