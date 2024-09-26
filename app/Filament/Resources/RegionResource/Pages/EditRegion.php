<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Models\Region;
use App\Filament\Resources\RegionResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;


class EditRegion extends EditRecord
{
    protected static string $resource = RegionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Region
    {
        $this->validateUniqueRegion($data['name'], $record->id);

        try {
            $record->update($data);
            NotificationHandler::sendSuccessNotification('Region update successful.', null);

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the region: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the region update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueRegion($name, $currentId)
    {
        $region = Region::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($region) {
            $message = $region->deleted_at 
                ? 'This region has been deleted. Restoration is required before it can be reused.' 
                : 'A region with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
