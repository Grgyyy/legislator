<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\InstitutionProgram;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateInstitutionProgram extends CreateRecord
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = 'Create Institution Qualification Titles';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-programs' => "Institution Qualification Title",
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateData($data);

        return $data;
    }

    protected function handleRecordCreation(array $data): InstitutionProgram
    {
        $this->validateData($data);

        $institutionProgram = DB::transaction(fn() => InstitutionProgram::create([
            'tvi_id' => $data['tvi_id'],
            'training_program_id' => $data['training_program_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', "Institution qualification title has been created successfully.");

        return $institutionProgram;
    }

    private function validateData(array $data): void
    {
        $institutionProgram = InstitutionProgram::where('tvi_id', $data['tvi_id'])
            ->where('training_program_id', $data['training_program_id'])
            ->exists();

        if ($institutionProgram) {
            NotificationHandler::handleValidationException(
                'Duplicate',
                'The selected qualification title is already associated to this institution.'
            );
        }
    }
}
