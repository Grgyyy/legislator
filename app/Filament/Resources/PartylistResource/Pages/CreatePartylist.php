<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use App\Helpers\Helper;
use App\Models\Partylist;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePartylist extends CreateRecord
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Create Party-list';

    public function getBreadcrumbs(): array
    {
        return [
            '/party-lists' => 'Party-lists',
            'Create'
        ];
    }
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Partylist
    {
        $this->validateUniquePartyList($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $partylist = DB::transaction(fn() => Partylist::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Party-list has been created successfully.');

        return $partylist;
    }

    protected function validateUniquePartyList($data)
    {
        $partyList = Partylist::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($partyList) {
            $message = $partyList->deleted_at
                ? 'This party-list has been deleted and must be restored before reuse.'
                : 'A party-list with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}