<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Models\Particular;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\ParticularResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateParticular extends CreateRecord
{
    protected static string $resource = ParticularResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Particular
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueParticular($data['name'], $data['district_id']);

            return Particular::create([
                'name' => $data['name'],
                'district_id' => $data['district_id'],
            ]);
        });
    }

    protected function validateUniqueParticular($name, $districtId)
    {
        $query = Particular::withTrashed()
            ->where('name', $name)
            ->where('district_id', $districtId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Particular exists in the district but is marked as deleted. You cannot create it again.';
            } else {
                $message = 'Particular with this name already exists in this district.';
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
