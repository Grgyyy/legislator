<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\DistrictResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;
    protected function getRedirectUrl(): string
    {
        $municipalityId = $this->record->municipality_id;

        if ($municipalityId) {
            return route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): District
    {
        $this->validateUniqueDistrict($data['name'], $data['municipality_id']);

        return DB::transaction(function () use ($data) {
            $district = District::create([
                'name' => $data['name'],
                'municipality_id' => $data['municipality_id'],
            ]);

            $this->sendCreationSuccessNotification($district);

            return $district;
        });
    }

    protected function validateUniqueDistrict($name, $municipalityId)
    {
        $query = District::withTrashed()
            ->where('name', $name)
            ->where('municipality_id', $municipalityId)
            ->first();

        if ($query) {
            $message = $query->deleted_at
                ? 'A district with this name exists in the municipality but is marked as deleted. Please restore it instead of creating a new one.'
                : 'A district with this name already exists in this municipality. Please choose a different name.';

            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('District Creation Failed')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }

    protected function sendCreationSuccessNotification($district)
    {
        Notification::make()
            ->title('District Created')
            ->body("{$district->name} has been successfully created.")
            ->success()
            ->send();
    }
}
