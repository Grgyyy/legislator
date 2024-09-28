<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Models\Province;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\ProvinceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

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

        return DB::transaction(function () use ($data) {

            $province = Province::create([
                'name' => $data['name'],
                'region_id' => $data['region_id'],
            ]);

            $this->sendCreationSuccessNotification($province);

            return $province;
        });
    }

    protected function validateUniqueProvince($name, $regionId)
    {
        $query = Province::withTrashed()
            ->where('name', $name)
            ->where('region_id', $regionId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Province exists in the region but is marked as deleted. Data cannot be created.';
            } else {
                $message = 'Province already exists in this region.';
            }
            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }

    protected function sendCreationSuccessNotification($province)
    {

        Notification::make()
            ->title('Province Created')
            ->body("{$province->name} has been successfully created.")
            ->success()
            ->send();
    }
}
