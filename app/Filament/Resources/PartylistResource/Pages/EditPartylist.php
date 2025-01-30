<?php

namespace App\Filament\Resources\PartylistResource\Pages;

use App\Models\Partylist;
use App\Filament\Resources\PartylistResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditPartylist extends EditRecord
{
    protected static string $resource = PartylistResource::class;

    protected static ?string $title = 'Edit Party-list';    

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/party-lists' => 'Party-lists',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): Partylist
    {
        $this->validateUniquePartyList($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);
        
        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Party-list has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the party-list: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the party-list update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniquePartyList($data, $currentId)
    {
        $partyList = Partylist::withTrashed()
            ->where('name', $data['name'])
            ->whereNot('id', $currentId)
            ->first();

        if ($partyList) {
            $message = $partyList->deleted_at
                ? 'A party-list with this name has been deleted and must be restored before reuse.'
                : 'A party-list with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}