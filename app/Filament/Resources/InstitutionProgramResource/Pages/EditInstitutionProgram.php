<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\InstitutionProgram;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditInstitutionProgram extends EditRecord
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = 'Edit Institution Qualification Title';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function handleRecordUpdate($record, array $data): InstitutionProgram
    {
        $this->validateData($data, $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Institution qualification title has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the institution qualification title: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the institution qualification title update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    private function validateData($data, $currentId): void
    {
        $institutionProgram = InstitutionProgram::where('tvi_id', $data['tvi_id'])
            ->where('training_program_id', $data['training_program_id'])
            ->whereNot('id', $currentId)
            ->exists();

        if ($institutionProgram) {
            NotificationHandler::handleValidationException(
                'Duplicate',
                'The selected qualification title is already associated to this institution.'
            );
        }
    }
}
