<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Models\TviType;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\TviTypeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTviType extends CreateRecord
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Create Institution Type';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Types',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): TviType
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueTviType($data['name']);

            return TviType::create([
                'name' => $data['name'],
            ]);
        });
    }

    protected function validateUniqueTviType($name)
    {
        $query = TviType::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Institution Type with this name exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'Institution Type with this name already exists.';
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
