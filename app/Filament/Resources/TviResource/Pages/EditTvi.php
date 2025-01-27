<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class EditTvi extends EditRecord
{
    protected static string $resource = TviResource::class;

    public function getHeading(): string
    {
        $record = $this->getRecord();
        return $record ? $record->name : 'Institution';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    public function getBreadcrumbs(): array
    {

        $record = $this->getRecord();

        return [
            route('filament.admin.resources.tvis.index') => $record ? $record->name : 'Institution',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Tvi
    {
        $this->validateUniqueInstitution($data, $record->id);

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
            ->where(DB::raw('LOWER(name)'), strtolower($data['name']))
            ->where('school_id', $data['school_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($tvi) {
            $message = $tvi->deleted_at
                ? 'This institution with the provided details has been deleted. Restoration is required before it can be reused.'
                : 'An institution with the provided details already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

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
