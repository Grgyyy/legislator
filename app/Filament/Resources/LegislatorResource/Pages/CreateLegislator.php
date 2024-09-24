<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Models\Legislator;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\LegislatorResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLegislator extends CreateRecord
{
    protected static string $resource = LegislatorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Legislator
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueLegislator($data['name']);

            $legislator = Legislator::create([
                'name' => $data['name'],
            ]);

            $this->sendCreationSuccessNotification($legislator);

            return $legislator;
        });
    }

    protected function validateUniqueLegislator($name)
    {
        $query = Legislator::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'A legislator with this name exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A legislator with this name already exists.';
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

    protected function sendCreationSuccessNotification($legislator)
    {
        Notification::make()
            ->title('Legislator Created')
            ->body("{$legislator->name} has been successfully created.")
            ->success()
            ->send();
    }
}
