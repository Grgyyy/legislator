<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Models\Province;
use App\Filament\Resources\ProvinceResource;
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
            return route('filament.admin.resources.regions.show_provinces', ['record' => $regionId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Province
    {
        $this->validateUniqueProvince($data['name'], $data['region_id']);

        $province = DB::transaction(fn() => Province::create([
            'name' => $data['name'],
            'region_id' => $data['region_id']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Province has been created successfully.');

        return $province;
    }

    protected function validateUniqueProvince($name, $regionId)
    {
        $province = Province::withTrashed()
            ->where('name', $name)
            ->where('region_id', $regionId)
            ->first();

        if ($province) {
            $message = $province->deleted_at 
                ? 'This province exists in the region but has been deleted; it must be restored before reuse.' 
                : 'A province with this name already exists in the specified region.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}