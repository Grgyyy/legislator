<?php

namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use App\Filament\Resources\InstitutionRecognitionResource;
use App\Models\InstitutionRecognition;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditInstitutionRecognition extends EditRecord
{
    protected static string $resource = InstitutionRecognitionResource::class;

    protected static ?string $title = 'Edit Institution Recognition';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function handleRecordUpdate($record, array $data): InstitutionRecognition
    {
        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Institution recognition has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the institution recognition: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the institution recognition update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }
}
