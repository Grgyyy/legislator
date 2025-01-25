<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Models\Priority;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditProjectProposal extends EditRecord
{
    protected static string $resource = ProjectProposalResource::class;

    public function getHeading(): string
    {
        $record = $this->getRecord();
        return $record ? $record->title : 'Project Proposal Programs';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }
    
    public function getBreadcrumbs(): array
    {

        $record = $this->getRecord();

        return [
            route('filament.admin.resources.training-programs.index') => $record ? $record->title : 'Project Proposal Programs',
            'Edit'
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected ?string $heading = 'Edit Project Proposal Program';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $trainingProgram = $record->trainingProgram;

        $data['program_name'] = $record->title;

        $data['scholarshipPrograms'] = $record->scholarshipPrograms()
            ->pluck('scholarship_programs.id')
            ->toArray();

        return $data;
    }

    public function disabledSoc(): bool
    {
        return true;
    }

    public function noQualiCode(): bool
    {
        return true;
    }

    public function noSocCode(): bool
    {
        return false;
    }

    public function noSchoPro(): bool
    {
        return false;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($data, $record) {
            $trainingProgram = TrainingProgram::withTrashed()
                ->where(DB::raw('LOWER(title)'), strtolower($data['title']))
                ->where('tvet_id', $data['tvet_id'])
                ->where('priority_id', $data['priority_id'])
                ->where('id', '!=', $record->id)
                ->first();

            if ($trainingProgram) {
                NotificationHandler::handleValidationException(
                    'Training Program Exists',
                    "The Training Program '{$trainingProgram->title}' already exists and cannot be updated."
                );
            }

            $record->update([
                'title'       => $data['title'],
                'priority_id' => $data['priority_id'],
                'tvet_id'     => $data['tvet_id'],
            ]);

            if (!empty($data['scholarshipPrograms'])) {
                $record->scholarshipPrograms()->detach();

                $record->scholarshipPrograms()->sync($data['scholarshipPrograms']);

                foreach ($data['scholarshipPrograms'] as $scholarshipProgramId) {
                    $scholarshipProgram = ScholarshipProgram::find($scholarshipProgramId);

                    $qualificationTitle = QualificationTitle::where('training_program_id', $record->id)
                        ->where('scholarship_program_id', $scholarshipProgram->id)
                        ->first();

                    if (!$qualificationTitle) {
                        QualificationTitle::create([
                            'training_program_id' => $record->id,
                            'scholarship_program_id' => $scholarshipProgram->id,
                            'status_id' => 1,
                            'soc' => 0
                        ]);
                    }
                }
            }

            NotificationHandler::sendSuccessNotification('Updated', 'The training program and scholarships have been updated successfully.');

            return $record;
        });
    }
}
