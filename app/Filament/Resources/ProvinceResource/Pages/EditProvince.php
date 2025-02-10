<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use App\Helpers\Helper;
use App\Models\Province;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditProvince extends EditRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getRedirectUrl(): string
    {
        $regionId = $this->record->region_id;

        if ($regionId) {
            return route('filament.admin.resources.provinces.showProvince', ['record' => $regionId]);
        }

        return $this->getResource()::getUrl('index');
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
    
    protected function handleRecordUpdate($record, array $data): Province
    {
        $this->validateUniqueProvince($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Province has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the province: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the province update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueProvince($data, $currentId)
    {
        $province = Province::withTrashed()
            ->where('name', $data['name'])
            ->where('region_id', $data['region_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($province) {
            $message = $province->deleted_at 
                ? 'A province with this name already exists in the region but has been deleted; it must be restored before reuse.' 
                : 'A province with this name already exists in the specified region.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = Province::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->whereNot('id', $currentId)
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A province with the provided PSG code already exists and has been deleted.' 
                    : 'A province with the provided PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}