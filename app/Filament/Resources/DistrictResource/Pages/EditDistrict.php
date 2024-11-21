<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Filament\Resources\DistrictResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    // protected function getRedirectUrl(): string
    // {
    //     $municipalityId = $this->record->municipality_id;

    //     if ($municipalityId) {
    //         return route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]);
    //     }

    //     return $this->getResource()::getUrl('index');
    // }

    protected function getRedirectUrl(): string
    {
        $municipalities = $this->record->municipalities;

        if ($municipalities->isNotEmpty()) {
            return route('filament.admin.resources.municipalities.showDistricts', [
                'record' => $municipalities->first()->id,
            ]);
        }

        return $this->getResource()::getUrl('index');
    }


    protected function handleRecordUpdate($record, array $data): District
    {
        $this->validateUniqueDistrict($data['name'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'District has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the district: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the district update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueDistrict($name, $currentId)
    {
        $district = District::withTrashed()
            ->where('name', $name)
            ->whereNot('id', $currentId)
            ->first();

        if ($district) {
            $message = $district->deleted_at
                ? 'This district exists in the municipality but has been deleted; it must be restored before reuse.'
                : 'A district with this name already exists in the specified municipality.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
