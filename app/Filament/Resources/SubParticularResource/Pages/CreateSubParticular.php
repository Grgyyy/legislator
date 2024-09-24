<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Models\SubParticular;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\SubParticularResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateSubParticular extends CreateRecord
{
    protected static string $resource = SubParticularResource::class;

    protected static ?string $title = 'Create Particular Type';

    public function getBreadcrumbs(): array
    {
        return [
            '/sub-particulars' => 'Particular Types',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function handleRecordCreation(array $data): SubParticular
    {
        $this->validateUniqueSubParticular($data['name']);

        return DB::transaction(function () use ($data) {
            $subParticular = SubParticular::create([
                'name' => $data['name'],
                'fund_source_id' => $data['fund_source_id'],
            ]);

            $this->sendCreationSuccessNotification($subParticular);

            return $subParticular;
        });
    }

    protected function validateUniqueSubParticular($name)
    {
        $existingSubParticular = SubParticular::withTrashed()
            ->where('name', $name)
            ->first();

        if ($existingSubParticular) {
            $message = $existingSubParticular->deleted_at
                ? 'A sub-particular with this name exists but is marked as deleted. Please restore it instead of creating a new one.'
                : 'A sub-particular with this name already exists. Please choose a different name.';

            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Sub-Particular Creation Failed')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }

    protected function sendCreationSuccessNotification($subParticular)
    {
        Notification::make()
            ->title('Sub-Particular Created')
            ->body("{$subParticular->name} has been successfully created.")
            ->success()
            ->send();
    }
}
