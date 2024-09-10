<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Models\TviClass;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\TviClassResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTviClass extends CreateRecord
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Create Institution Class';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Classes',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): TviClass
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueTviClass($data['name']);

            return TviClass::create([
                'name' => $data['name'],
                'tvi_type_id' => $data['tvi_type_id'],
            ]);
        });
    }

    protected function validateUniqueTviClass($name)
    {
        $query = TviClass::withTrashed()
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
