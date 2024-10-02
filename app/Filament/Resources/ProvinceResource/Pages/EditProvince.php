<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Models\Province;
use App\Filament\Resources\ProvinceResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditProvince extends EditRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getRedirectUrl(): string
    {
        $regionId = $this->record->region_id;

        if ($regionId) {
            return route('filament.admin.resources.regions.show_provinces', ['record' => $regionId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Province
    {
        $this->validateUniqueProvince($data['name'], $data['region_id'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Province update successful', null);

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the province: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the province update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueProvince($name, $regionId, $currentId)
    {
        $province = Province::withTrashed()
            ->where('name', $name)
            ->where('region_id', $regionId)
            ->where('id', '!=', $currentId)
            ->first();

        if ($province) {
            $message = $province->deleted_at 
                ? 'This province exists in the region but has been deleted; it must be restored before reuse.' 
                : 'A province with this name already exists in the specified region.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}