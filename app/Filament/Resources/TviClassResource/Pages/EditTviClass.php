<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Filament\Resources\TviClassResource;
use App\Helpers\Helper;
use App\Models\TviClass;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditTviClass extends EditRecord
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Edit Institution Class';

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-classes-a' => 'Institution Classes',
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): TviClass
    {
        $this->validateUniqueTviClass($data, $record->id);

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

    protected function validateUniqueTviClass($data, $currentId)
    {
        $tviClass = TviClass::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->whereNot('id', $currentId)
            ->first();

        if ($tviClass) {
            $message = $tviClass->deleted_at
                ? 'An institution class with this name has been deleted and must be restored before reuse.'
                : 'An institution class with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}