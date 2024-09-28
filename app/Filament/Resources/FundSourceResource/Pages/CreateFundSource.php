<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use App\Models\FundSource;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\FundSourceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateFundSource extends CreateRecord
{
    protected static string $resource = FundSourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function handleRecordCreation(array $data): FundSource
    {
        $this->validateUniqueFundSource($data['name']);

        return DB::transaction(function () use ($data) {
            $fundSource = FundSource::create([
                'name' => $data['name'],
            ]);

            $this->sendCreationSuccessNotification($fundSource);

            return $fundSource;
        });
    }
    protected function validateUniqueFundSource($name)
    {
        $existingFundSource = FundSource::withTrashed()
            ->where('name', $name)
            ->first();

        if ($existingFundSource) {
            $message = $existingFundSource->deleted_at
                ? 'A fund source with this name exists but is marked as deleted. Please restore it instead of creating a new one.'
                : 'A fund source with this name already exists. Please choose a different name.';

            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Fund Source Creation Failed')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }

    protected function sendCreationSuccessNotification($fundSource)
    {
        Notification::make()
            ->title('Fund Source Created')
            ->body("{$fundSource->name} has been successfully created.")
            ->success()
            ->send();
    }
}
