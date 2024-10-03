<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Models\Legislator;
use App\Filament\Resources\LegislatorResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditLegislator extends EditRecord
{
    protected static string $resource = LegislatorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Legislator
    {
        $this->validateUniqueLegislator($data['name'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Legislator has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the legislator: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the legislator update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueLegislator($name, $currentId)
    {
        $legislator = Legislator::withTrashed()
            ->where('name', $name)
            ->whereNot('id', $currentId)
            ->first();

        if ($legislator) {
            $message = $legislator->deleted_at 
                ? 'This legislator has been deleted. Restoration is required before it can be reused.' 
                : 'A legislator with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}