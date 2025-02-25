<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\InstitutionProgram;
use App\Services\NotificationHandler;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditInstitutionProgram extends EditRecord
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = 'Edit Institution Qualification Titles';

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
        return [
            '/institution-programs' => "Institution Qualification Titles",
            'Edit',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate($record, array $data): InstitutionProgram
    {
        $this->validateData($data, $record);

        $record->update($data);

        NotificationHandler::sendSuccessNotification('Saved', 'Institution has been updated successfully.');

        return $record;

    }

    private function validateData($data, $record): void
    {
        $exists = InstitutionProgram::where('tvi_id', $data['tvi_id'])
            ->where('training_program_id', $data['training_program_id'])
            ->whereNot('id', $record->id)
            ->exists();

        if ($exists) {
            NotificationHandler::handleValidationException(
                'Duplicate Association Detected',
                'The selected training program is already linked to this institution. Please choose a different training program or institution.'
            );
        }
    }

}
