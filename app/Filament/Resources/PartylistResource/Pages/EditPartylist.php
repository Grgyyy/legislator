<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Filament\Resources\PartylistResource;
use App\Models\FundSource;
use App\Models\Partylist;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditPartylist extends EditRecord
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Edit Party-List';

    public function getBreadcrumbs(): array
    {
        return [
            '/partylists' => 'Party-List',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Partylist
    {
        $this->validateUniquePartyList($data['name'], $record->id);

        try {
            $record->update($data);
            
            NotificationHandler::sendSuccessNotification('Party-List update successful', null);

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the party-list: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the party-list update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniquePartyList($name, $currentId)
    {
        $partyList = Partylist::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($partyList) {
            $message = $partyList->deleted_at 
                ? 'This party-list has been deleted. Restoration is required before it can be reused.' 
                : 'A party-list with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}