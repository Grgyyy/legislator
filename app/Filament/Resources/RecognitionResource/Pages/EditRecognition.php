<?php

namespace App\Filament\Resources\RecognitionResource\Pages;

use App\Filament\Resources\RecognitionResource;
use App\Helpers\Helper;
use App\Models\Recognition;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditRecognition extends EditRecord
{
    protected static string $resource = RecognitionResource::class;

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

    protected function handleRecordUpdate($record, array $data): Recognition
    {
        $this->validateUniqueInstitution($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Recognition has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the recognition: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the recognition update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueInstitution($data, $id)
    {
        $recognition = Recognition::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->whereNot('id', $id)
            ->first();

        if ($recognition) {
            $message = $recognition->deleted_at
                ? 'A recognition title with this name has been deleted and must be restored before reuse.'
                : 'A recognition title with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
