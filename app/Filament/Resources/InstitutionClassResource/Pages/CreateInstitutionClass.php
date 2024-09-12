<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Models\InstitutionClass;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\InstitutionClassResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateInstitutionClass extends CreateRecord
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): InstitutionClass
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueInstitutionClass($data['name']);

            return InstitutionClass::create([
                'name' => $data['name'],
            ]);
        });
    }

    protected function validateUniqueInstitutionClass($name)
    {
        $query = InstitutionClass::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Institution Class with this name exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'Institution Class with this name already exists.';
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
