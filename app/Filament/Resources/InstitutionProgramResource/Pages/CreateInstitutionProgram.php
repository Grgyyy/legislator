<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\InstitutionProgram;
use App\Services\NotificationHandler;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\Rule;

class CreateInstitutionProgram extends CreateRecord
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = 'Associate Qualification Title with Institution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-programs' => "Institution's Qualification Titles",
            'Add',
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
