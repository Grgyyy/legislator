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
        return DB::transaction(function () use ($data) {
            $this->validateUniqueMunicipality($data['name'], $data['province_id']);

            return Municipality::create([
                'name' => $data['name'],
                'province_id' => $data['province_id'],
            ]);
        });
    }

    protected function validateUniqueMunicipality($name, $provinceId)
    {
        $query = Municipality::withTrashed()
            ->where('name', $name)
            ->where('province_id', $provinceId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Municipality exists in the province but is marked as deleted. You cannot create it again.';
            } else {
                $message = 'Municipality with this name already exists in this province.';
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
}
