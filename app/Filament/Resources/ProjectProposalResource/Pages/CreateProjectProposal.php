<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Helpers\Helper;
use App\Models\Priority;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProjectProposal extends CreateRecord
{
    protected static string $resource = ProjectProposalResource::class;

    protected static ?string $title = 'Create Project Proposal Program';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
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
    
    public function getBreadcrumbs(): array
    {
        return [
            '/project-proposals' => 'Project Proposal Programs',
            'Create',
        ];
    }

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

    protected function handleRecordCreation(array $data): TrainingProgram
    {
        $this->validateUniqueProjectProposal($data);

        $data['title'] = Helper::capitalizeWords($data['title']);
        $formattedSocCode = $this->generateSocCode();

        $projectProposalProgram = DB::transaction(function () use ($data, $formattedSocCode) {
            $priority = Priority::where('name', 'Not Applicable')->first();
            $tvet = Tvet::where('name', 'Not Applicable')->first();

            $projectProposalProgram = TrainingProgram::create([
                'soc_code' => $formattedSocCode,
                'title' => $data['title'],
                'priority_id' => $priority->id,
                'tvet_id' => $tvet->id,
                'soc' => 0,
            ]);

            $scholarshipProgramIds = ScholarshipProgram::whereIn('code', ['TTSP', 'TWSP'])->pluck('id');


            if ($scholarshipProgramIds->isNotEmpty()) {
                $projectProposalProgram->scholarshipPrograms()->sync($scholarshipProgramIds);

                foreach ($scholarshipProgramIds as $scholarshipProgramId) {
                    $exists = QualificationTitle::where('training_program_id', $projectProposalProgram->id)
                        ->where('scholarship_program_id', $scholarshipProgramId)
                        ->where('soc', 0)
                        ->exists();

                    if (!$exists) {
                        QualificationTitle::create([
                            'training_program_id' => $projectProposalProgram->id,
                            'scholarship_program_id' => $scholarshipProgramId,
                            'status_id' => 1,
                            'soc' => 0,
                        ]);
                    }
                }
            }

            return $projectProposalProgram;
        });

        NotificationHandler::sendSuccessNotification('Created', 'Qualification title has been created successfully.');
        return $projectProposalProgram;
    }

    private function generateSocCode(): string
    {
        $lastTrainingProgram = TrainingProgram::where('soc_code', 'like', 'PROP%')
            ->orderByDesc('soc_code')
            ->first();

        $lastSocCode = $lastTrainingProgram ? substr($lastTrainingProgram->soc_code, 4) : 0;
        $lastSocCode = ltrim($lastSocCode, '0');
        $newSocCode = (int) $lastSocCode + 1;

        return sprintf('PROP%06d', $newSocCode);
    }

    private function validateUniqueProjectProposal($data)
    {
        $trainingProgram = TrainingProgram::withTrashed()
            ->where('title', $data['title'])
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at
                ? 'A qualification title with the provided details has been deleted and must be restored before reuse.'
                : 'A qualification title with the provided details already exists.';

            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
