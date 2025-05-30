<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Filament\Resources\ProvinceResource;
use App\Helpers\Helper;
use App\Models\Province;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProvince extends CreateRecord
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }
    
    protected function handleRecordCreation(array $data): Province
    {
        $this->validateUniqueProvince($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $province = DB::transaction(fn() => Province::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'region_id' => $data['region_id']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Province has been created successfully.');

        return $province;
    }

    protected function validateUniqueProvince($data)
    {
        $province = Province::withTrashed()
            ->where('name', $data['name'])
            ->where('region_id', $data['region_id'])
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