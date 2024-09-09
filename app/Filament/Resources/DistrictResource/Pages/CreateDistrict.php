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
        return DB::transaction(function () use ($data) {
            $this->validateUniqueDistrict($data['name'], $data['municipality_id']);

            return District::create([
                'name' => $data['name'],
                'municipality_id' => $data['municipality_id'],
            ]);
        });
    }

    protected function validateUniqueDistrict($name, $municipalityId)
    {
        $query = District::withTrashed()
            ->where('name', $name)
            ->where('municipality_id', $municipalityId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'District exists in the municipality but is marked as deleted. You cannot create it again.';
            } else {
                $message = 'District with this name already exists in this municipality.';
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
