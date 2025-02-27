<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\InstitutionProgram;
use App\Services\NotificationHandler;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            '/institution-programs' => "Institution Qualification Titles",
            'Create',
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validation to check unique tvi_id and training_program_id combination
        $this->validateData($data);

        return $data;
    }

    protected function handleRecordCreation(array $data): InstitutionProgram
    {
        $this->validateData($data);

        $institutionProgram = DB::transaction(fn() => InstitutionProgram::create($data));

        NotificationHandler::sendSuccessNotification('Created', "Institution's Training Program has been created successfully.");

        return $institutionProgram;
    }

    private function validateData(array $data): void
    {
        $exists = InstitutionProgram::where('tvi_id', $data['tvi_id'])
            ->where('training_program_id', $data['training_program_id'])
            ->exists();

        if ($exists) {
            NotificationHandler::handleValidationException(
                'Duplicate Association Detected',
                'The selected training program is already linked to this institution. Please choose a different training program or institution.'
            );
        }
    }
}
