<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Filament\Resources\InstitutionClassResource;
use App\Helpers\Helper;
use App\Models\InstitutionClass;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditInstitutionClass extends EditRecord
{
    protected static string $resource = InstitutionClassResource::class;

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
            '/institution-classes-b' => 'Institution Classes',
            'Edit'
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

    protected function handleRecordUpdate($record, array $data): InstitutionClass
    {
        $this->validateUniqueInstitutionClass($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Institution class has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the institution class: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the institution class update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueInstitutionClass($data, $currentId)
    {
        $institutionClass = InstitutionClass::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->whereNot('id', $currentId)
            ->first();

        if ($institutionClass) {
            $message = $institutionClass->deleted_at
                ? 'An institution class with this name has been deleted. Restoration is required before it can be reused.'
                : 'An institution class with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}