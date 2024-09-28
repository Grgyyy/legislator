<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Models\Partylist;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\PartylistResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePartylist extends CreateRecord
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Create Party-List';

    public function getBreadcrumbs(): array
    {
        return [
            '/partylists' => 'Party-List',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Partylist
    {
        $this->validateUniquePartylist($data['name']);

        return DB::transaction(function () use ($data) {
            $partylist = Partylist::create([
                'name' => $data['name'],
            ]);

            $this->sendCreationSuccessNotification($partylist);

            return $partylist;
        });
    }

    protected function validateUniquePartylist($name)
    {
        $existingPartylist = Partylist::withTrashed()
            ->where('name', $name)
            ->first();

        if ($existingPartylist) {
            $message = $existingPartylist->deleted_at
                ? 'A partylist with this name exists but is marked as deleted. Please restore it instead of creating a new one.'
                : 'A partylist with this name already exists. Please choose a different name.';

            $this->handleValidationException($message);
        }
    }
    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Partylist Creation Failed')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }

    protected function sendCreationSuccessNotification($partylist)
    {
        Notification::make()
            ->title('Partylist Created')
            ->body("{$partylist->name} has been successfully created.")
            ->success()
            ->send();
    }
}
