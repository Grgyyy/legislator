<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Models\Tvi;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\TviResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTvi extends CreateRecord
{
    protected static string $resource = TviResource::class;
    protected static ?string $title = 'Create Institution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institutions',
            'Create',
        ];
    }

    // Handle record creation with validation for unique institutions
    protected function handleRecordCreation(array $data): Tvi
    {
        return DB::transaction(function () use ($data) {
            // Validate the uniqueness of the institution
            $this->validateUniqueInstitution($data);

            // Proceed to create the institution record
            return Tvi::create($data);
        });
    }

    // Custom validation for unique institution
    protected function validateUniqueInstitution($data)
    {
        $query = Tvi::withTrashed()
            ->where('name', $data['name'])
            ->where('institution_class_id', $data['institution_class_id'])
            ->where('tvi_class_id', $data['tvi_class_id'])
            ->where('district_id', $data['district_id'])
            ->where('address', $data['address'])
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'An institution with the same data exists and is marked as deleted. You cannot create it again.';
            } else {
                $message = 'An institution with the same data already exists.';
            }
            $this->handleValidationException($message);
        }
    }

    // Handle validation exceptions and show notifications
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
