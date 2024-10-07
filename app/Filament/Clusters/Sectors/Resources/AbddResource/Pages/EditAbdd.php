<?php

namespace App\Filament\Clusters\Sectors\Resources\AbddResource\Pages;

use App\Models\Abdd;
use App\Filament\Clusters\Sectors\Resources\AbddResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditAbdd extends EditRecord
{
    protected static string $resource = AbddResource::class;

    protected static ?string $title = 'Edit ABDD Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/abdds' => 'ABDD Sectors',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Abdd
    {
        $this->validateUniqueAbdd($data['name'], $record->id);

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

    protected function validateUniqueAbdd($name, $currentId)
    {
        $abdd = Abdd::withTrashed()
            ->where('name', $name)
            ->whereNot('id', $currentId)
            ->first();

        if ($abdd) {
            $message = $abdd->deleted_at 
                ? 'This ABDD sector has been deleted. Restoration is required before it can be reused.' 
                : 'An ABDD sector with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}