<?php

namespace App\Filament\Clusters\Sectors\Resources\AbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\AbddResource;
use App\Helpers\Helper;
use App\Models\Abdd;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditAbdd extends EditRecord
{
    protected static string $resource = AbddResource::class;

    protected static ?string $title = 'Edit ABDD Sectors';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/abdds' => 'ABDD Sectors',
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

    protected function handleRecordUpdate($record, array $data): Abdd
    {
        $this->validateUniqueAbdd($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'ABDD sector has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the ABDD sector: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the ABDD sector update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueAbdd($data, $currentId)
    {
        $abdd = Abdd::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->whereNot('id', $currentId)
            ->first();

        if ($abdd) {
            $message = $abdd->deleted_at
                ? 'An ABDD sector with this name has been deleted and must be restored before reuse.'
                : 'An ABDD sector with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}