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

class ConvertProjectProposal extends EditRecord
{
    protected static string $resource = ProjectProposalResource::class;

    public function getHeading(): string
    {
        $record = $this->getRecord();
        return $record ? $record->title : 'Project Proposal Programs';
    }
    
    public function getBreadcrumbs(): array
    {

        $record = $this->getRecord();

        return [
            route('filament.admin.resources.training-programs.index') => $record ? $record->title : 'Project Proposal Programs',
            'Convert into Training Program'
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.training-programs.index');
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
        return false;
    }

    public function noQualiCode(): bool
    {
        return false;
    }

    public function noSocCode(): bool
    {
        return false;
    }

    public function noSchoPro(): bool
    {
        return true;
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
                'code' => $data['code'],
                'soc_code' => $data['soc_code'],
                'title'       => $data['title'],
                'priority_id' => $data['priority_id'],
                'tvet_id'     => $data['tvet_id'],
                'soc' => 1
            ]);

            NotificationHandler::sendSuccessNotification(
                'Conversion Successful',
                'The Project Proposal Program has been successfully converted into a Qualification Title and is now ready for costing in the Schedule of Cost.'
            );

            return $record;
        });
    }
}
