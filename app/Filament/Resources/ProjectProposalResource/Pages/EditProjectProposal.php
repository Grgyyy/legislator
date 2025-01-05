<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Models\Priority;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Models\QualificationTitle;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProjectProposal extends EditRecord
{
    protected static string $resource = ProjectProposalResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.project-proposals.index') => 'Project Proposal Programs',
            'Edit',
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

        $data['program_name'] = ucwords($trainingProgram->title);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $programName = ucwords($data['program_name']);
            $trainingProgram = TrainingProgram::where('title', $programName)->first();

            if ($trainingProgram) {
                QualificationTitle::where('training_program_id', $record->training_program_id)
                    ->update(['training_program_id' => $trainingProgram->id]);
            } else {
                $tvetSector = Tvet::where('name', 'Not Applicable')->firstOrFail();
                $prioSector = Priority::where('name', 'Not Applicable')->firstOrFail();
                $createdTrainingProgram = TrainingProgram::create([
                    'title' => $programName,
                    'tvet_id' => $tvetSector->id,
                    'priority_id' => $prioSector->id,
                ]);

                QualificationTitle::where('training_program_id', $record->training_program_id)
                    ->update(['training_program_id' => $createdTrainingProgram->id]);
            }

            return $record;
        });
    }
}
