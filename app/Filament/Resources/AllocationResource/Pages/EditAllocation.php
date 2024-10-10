<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Models\Allocation;
use App\Filament\Resources\AllocationResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditAllocation extends EditRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Allocation
    {
        $this->validateUniqueAllocation($data, $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Allocation has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the allocation: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the allocation update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueAllocation(array $data, $currentId)
    {
        $allocation = Allocation::withTrashed()
            ->where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->where('id', '!=', $currentId)
            ->first();

        if ($allocation) {
            $message = $allocation->deleted_at
                ? 'This allocation with the provided details has been deleted. Restoration is required before it can be reused.'
                : 'This Allocation with the provided details already exists.';

            $this->handleValidationException($message);
        }
    }
}