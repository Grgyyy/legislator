<?php

namespace App\Filament\Clusters\Sectors\Resources\AbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\AbddResource;
use App\Models\Abdd;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAbdd extends CreateRecord
{
    protected static string $resource = AbddResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/abdds' => 'ABDD Sectors',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Abdd
    {
        return DB::transaction(function () use ($data) {
            // $this->validateUniqueAbdd($data['name']);

            return Abdd::create([
                'name' => $data['name'],
            ]);
        });
    }

    protected function validateUniqueAbdd($name)
    {
        $query = Abdd::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'ABDD Sector data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'ABDD Sector data already exists.';
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
