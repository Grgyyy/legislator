<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\MunicipalityResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMunicipality extends CreateRecord
{
    protected static string $resource = MunicipalityResource::class;

    /**
     * Get the redirect URL after a municipality is created
     */
    protected function getRedirectUrl(): string
    {
        $provinceId = $this->record->province_id;

        if ($provinceId) {
            return route('filament.admin.resources.provinces.showMunicipalities', ['record' => $provinceId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Municipality
    {
        $this->validateUniqueMunicipality($data['name'], $data['province_id']);

        return DB::transaction(function () use ($data) {
            $municipality = Municipality::create([
                'name' => $data['name'],
                'province_id' => $data['province_id'],
            ]);

            $this->sendCreationSuccessNotification($municipality);

            return $municipality;
        });
    }

    protected function validateUniqueMunicipality($name, $provinceId)
    {
        $query = Municipality::withTrashed()
            ->where('name', $name)
            ->where('province_id', $provinceId)
            ->first();

        if ($query) {
            $message = $query->deleted_at
                ? 'A municipality with this name exists in the province but is marked as deleted. Please restore it instead of creating a new one.'
                : 'A municipality with this name already exists in this province. Please choose a different name.';

            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Municipality Creation Failed')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }

    protected function sendCreationSuccessNotification($municipality)
    {
        Notification::make()
            ->title('Municipality Created')
            ->body("{$municipality->name} has been successfully created.")
            ->success()
            ->send();
    }
}
