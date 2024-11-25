<?php
namespace App\Filament\Resources\DistrictResource\Pages;

use Exception;
use App\Models\District;
use App\Models\Province;
use App\Models\Municipality;
use App\Services\NotificationHandler;
use Illuminate\Database\QueryException;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\DistrictResource;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): District
    {
        // Handle the case where municipality_id might be empty and needs to be adjusted based on the province
        if (empty($data['municipality_id']) && isset($data['province_id'])) {
            $province = Province::with('region')->find($data['province_id']);

            if ($province && $province->region->name !== 'NCR') {
                $data['municipality_id'] = null;  // Adjust the logic as needed
            }
        }

        // Validate the uniqueness of the district, checking the current ID to avoid conflicts
        $this->validateUniqueDistrict($data['name'], $record->id, $data['code'], $data['municipality_id'], $data['province_id']);

        try {
            // Update the district record itself
            $record->update($data);

            // If 'municipality_ids' are provided, update the many-to-many relationship in the junction table
            if (!empty($data['municipality_ids'])) {
                $this->updateDistrictMunicipalities($record, $data['municipality_ids']);
            } elseif (!empty($data['municipality_id'])) {
                // If only one municipality_id is provided, update the pivot table with it
                $record->municipality()->sync([$data['municipality_id']]);
            }

            NotificationHandler::sendSuccessNotification('Saved', 'District has been updated successfully.');
            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the district: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the district update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    /**
     * Update the district-municipality relationship in the junction table.
     *
     * @param District $record
     * @param array $municipalityIds
     * @return void
     */
    protected function updateDistrictMunicipalities(District $record, array $municipalityIds): void
    {
        try {
            // Use sync to ensure the junction table is correctly updated
            $record->municipality()->sync($municipalityIds);
            NotificationHandler::sendSuccessNotification('Updated', 'Municipalities have been updated for the district.');
        } catch (\Exception $e) {
            \Log::error('Failed to update municipalities for district: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function validateUniqueDistrict($name, $currentId, $code, $municipalityId, $provinceId)
    {
        // Check if the district with the same name, code, and municipality already exists, excluding the current record
        $district = District::withTrashed()
            ->where('name', $name)
            ->where('code', $code)
            ->where('province_id', $provinceId)
            ->where('municipality_id', $municipalityId)
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
