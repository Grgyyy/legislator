<?php
namespace App\Filament\Resources\DistrictResource\Pages;

use Exception;
use App\Models\District;
use App\Models\Province;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationHandler;
use Illuminate\Database\QueryException;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\DistrictResource;
use App\Helpers\Helper;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function handleRecordUpdate($record, array $data): District
    {
        if (empty($data['municipality_id']) && isset($data['province_id'])) {
            $province = Province::with('region')->find($data['province_id']);

            if ($province && $province->region->name !== 'NCR') {
                $data['municipality_id'] = null;
            }
        }

        $this->validateUniqueDistrict($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);

            if (!empty($data['municipality_ids'])) {
                $this->updateDistrictMunicipalities($record, $data['municipality_ids']);
            } elseif (!empty($data['municipality_id'])) {
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

    protected function updateDistrictMunicipalities(District $record, array $municipalityIds): void
    {
        try {
            $record->municipality()->sync($municipalityIds);
            NotificationHandler::sendSuccessNotification('Updated', 'Municipalities have been updated for the district.');
        } catch (Exception $e) {
            Log::error('Failed to update municipalities for district: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function validateUniqueDistrict($data, $currentId)
    {
        $districtQuery = District::withTrashed()
            ->where('name', $data['name'])
            ->where('province_id', $data['province_id'])
            ->whereNot('id', $currentId);

        if (!empty($data['municipality_id'])) {
            $districtQuery->where('municipality_id', $data['municipality_id']);
        }

        $district = $districtQuery->first();

        if ($district) {
            if (!empty($data['municipality_id'])) {
                $message = $district->deleted_at
                    ? 'This district exists in the municipality but has been deleted; it must be restored before reuse.'
                    : 'A district with this name already exists in the specified municipality.';
            } else {
                $message = $district->deleted_at
                    ? 'This district exists in the province but has been deleted; it must be restored before reuse.'
                    : 'A district with this name already exists in the specified province.';
            }

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = District::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->whereNot('id', $currentId)
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A district with this PSG code already exists and has been deleted.' 
                    : 'A district with this PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}
