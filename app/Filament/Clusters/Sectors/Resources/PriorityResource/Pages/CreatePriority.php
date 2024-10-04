<?php

namespace App\Filament\Clusters\Sectors\Resources\PriorityResource\Pages;

use App\Models\Priority;
use Illuminate\Support\Facades\DB;
use App\Filament\Clusters\Sectors\Resources\PriorityResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePriority extends CreateRecord
{
    protected static string $resource = PriorityResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/priorities' => 'Top Ten Priority Sectors',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Priority
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniquePriority($data['name']);

            return Priority::create([
                'name' => $data['name'],
            ]);
        });
    }

    protected function validateUniquePriority($name)
    {
        $query = Priority::withTrashed()
            ->where('name', $name)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Priority Sector data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'Priority Sector data already exists.';
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
