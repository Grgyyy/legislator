<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Models\Particular;
use App\Filament\Resources\ParticularResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditParticular extends EditRecord
{
    protected static string $resource = ParticularResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Particular
    {
        $this->validateUniqueParticular($data['sub_particular_id'], $record->id);
        $this->validateUniqueParticular($data['sub_particular_id'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Success', 'Particular has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the particular: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the particular update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueParticular($sub_particular_id, $currentId)
    {
        $particular = Particular::withTrashed()
            ->where('sub_particular_id', $sub_particular_id)
            ->where('id', '!=', $currentId)
            ->first();

        if ($particular) {
            $message = $particular->deleted_at 
                ? 'This particular has been deleted. Restoration is required before it can be reused.' 
                : 'A particular with the specified type and administrative area already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
