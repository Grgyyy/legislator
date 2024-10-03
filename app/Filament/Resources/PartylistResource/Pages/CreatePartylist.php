<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Models\Partylist;
use App\Filament\Resources\PartylistResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePartylist extends CreateRecord
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Create Party-List';
    
    public function getBreadcrumbs(): array
    {
        return [
            '/partylists' => 'Party-Lists',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Partylist
    {
        $this->validateUniquePartyList($data['name']);

        $partylist = DB::transaction(fn() => Partylist::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Party-list has been created successfully.');

        return $partylist;
    }

    protected function validateUniquePartyList($name)
    {
        $partyList = Partylist::withTrashed()
            ->where('name', $name)
            ->first();

        if ($partyList) {
            $message = $partyList->deleted_at 
                ? 'This party-list has been deleted and must be restored before reuse.' 
                : 'A party-list with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}