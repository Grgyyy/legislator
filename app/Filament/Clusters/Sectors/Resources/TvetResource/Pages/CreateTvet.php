<?php

namespace App\Filament\Clusters\Sectors\Resources\TvetResource\Pages;

use App\Models\Tvet;
use Illuminate\Support\Facades\DB;
use App\Filament\Clusters\Sectors\Resources\TvetResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTvet extends CreateRecord
{
    protected static string $resource = TvetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/tvets' => 'TVET Sectors',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Tvet
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueTvet($data['name']);

            return Tvet::create([
                'name' => $data['name'],
            ]);
        });
    }

    protected function validateUniqueTvet($name)
    {
        $query = Tvet::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'TVET Sector data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'TVET Sector data already exists.';
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
