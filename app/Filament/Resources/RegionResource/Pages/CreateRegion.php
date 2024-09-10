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
        return DB::transaction(function () use ($data) {
            $this->validateUniqueRegion($data['name']);

            return Region::create([
                'name' => $data['name'],
            ]);
        });
    }

    protected function validateUniqueRegion($name)
    {
        $query = Region::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Region already exists but is marked as deleted. You cannot create it again.';
            } else {
                $message = 'Region with this name already exists.';
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
