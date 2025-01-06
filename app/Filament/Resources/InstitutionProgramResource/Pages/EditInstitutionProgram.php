<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Services\NotificationHandler;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use App\Models\InstitutionProgram;

class EditInstitutionProgram extends EditRecord
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = 'Edit Training Program Association with Institution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-programs' => "Institution's Training Program",
            'Edit',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate($record, array $data): InstitutionProgram
    {
        $this->validateData($data);

        $record->update($data);

        NotificationHandler::sendSuccessNotification('Saved', 'Institution has been updated successfully.');

        return $record;

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
