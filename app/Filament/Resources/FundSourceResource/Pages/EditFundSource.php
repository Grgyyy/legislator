<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use App\Models\FundSource;
use App\Filament\Resources\FundSourceResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditFundSource extends EditRecord
{
    protected static string $resource = FundSourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): FundSource
    {
        $this->validateUniqueFundSource($data['name'], $record->id);

        try {
            $record->update($data);
            
            NotificationHandler::sendSuccessNotification('Saved', 'Fund source has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the fund source: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the fund source update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueFundSource($name, $currentId)
    {
        $fundSource = FundSource::withTrashed()
            ->where('name', $name)
            ->whereNot('id', $currentId)
            ->first();

        if ($fundSource) {
            $message = $fundSource->deleted_at 
                ? 'This fund source has been deleted. Restoration is required before it can be reused.' 
                : 'A fund source with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}