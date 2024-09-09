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

            return Legislator::create([
                'name' => $data['name'],
            ]);
        });
    }

    protected function validateUniqueLegislator($name)
    {
        $query = Legislator::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Legislator with this name exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'Legislator with this name already exists.';
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
