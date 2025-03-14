<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use App\Helpers\Helper;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditTvi extends EditRecord
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Edit Institution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/institutions' => 'Institutions',
            'Edit'
        ];
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

    protected function handleRecordUpdate($record, array $data): Tvi
    {
        $this->validateUniqueInstitution($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Institution has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the institution: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the institution update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueInstitution(array $data, $currentId)
    {
        $tvi = Tvi::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->where('school_id', $data['school_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($tvi) {
            $message = $tvi->deleted_at
                ? 'This institution with the provided details has been deleted and must be restored before reuse.'
                : 'An institution with the provided details already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $schoolId = Tvi::withTrashed()
                ->where('school_id', $data['school_id'])
                ->whereNot('id', $currentId)
                ->first();

            if ($schoolId) {
                $message = $schoolId->deleted_at
                    ? 'An institution with this school ID already exists and has been deleted.'
                    : 'An institution with this school ID already exists.';

                NotificationHandler::handleValidationException('Invalid School ID', $message);
            }
        }
    }
}
