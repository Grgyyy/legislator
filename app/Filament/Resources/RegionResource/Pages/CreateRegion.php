<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Models\Region;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\RegionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Region
    {

        $this->validateUniqueRegion($data['name']);

        return DB::transaction(function () use ($data) {

            $region = Region::create([
                'name' => $data['name'],
            ]);


            $this->sendCreationSuccessNotification($region);

            return $region;
        });
    }

    protected function validateUniqueRegion($name)
    {
        $query = Region::withTrashed()
            ->where('name', $name)
            ->first();


        if ($query) {
            $message = $query->deleted_at
                ? 'A region with this name exists but is marked as deleted. Please restore it instead of creating a new one.'
                : 'A region with this name already exists. Please choose a different name to create a new region.';

            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Region Creation Failed')
            ->body($message)
            ->danger()
            ->send();


        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }
    protected function sendCreationSuccessNotification($region)
    {

        Notification::make()
            ->title('Region Created')
            ->body("The '{$region->name}' has been successfully created.")
            ->success()
            ->send();
    }
}
