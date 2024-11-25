<?php
namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\District;
use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\DB;

class EditMunicipality extends EditRecord
{
    protected static string $resource = MunicipalityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Municipality
    {
        $this->validateUniqueMunicipality($data['name'], $data['class'], $data['code'], $data['province_id'], $record->id);

        try {
            DB::transaction(function () use ($record, $data) {
                // Update the municipality record
                $record->update([
                    'name' => $data['name'],
                    'class' => $data['class'],
                    'code' => $data['code'],
                    'province_id' => $data['province_id'],
                    'district_id' => $data['district_id']
                ]);

                // If a district ID is provided, update the relationship
                if (!empty($data['district_id'])) {
                    $district = District::find($data['district_id']);
                    if ($district) {
                        $district->municipality()->syncWithoutDetaching([$record->id]);
                    }
                }
            });

            NotificationHandler::sendSuccessNotification('Saved', 'Municipality has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the municipality: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the municipality update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueMunicipality($name, $class, $code, $provinceId, $currentId)
    {
        $municipality = Municipality::withTrashed()
            ->where('name', $name)
            ->where('class', $class)
            ->where('code', $code)
            ->where('province_id', $provinceId)
            ->whereNot('id', $currentId)
            ->first();

        if ($municipality) {
            $message = $municipality->deleted_at
                ? 'This municipality exists in the region but has been deleted; it must be restored before reuse.'
                : 'A municipality with this name already exists in the specified region.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
