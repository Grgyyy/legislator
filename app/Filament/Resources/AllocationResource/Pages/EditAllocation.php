<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Models\Allocation;
use App\Filament\Resources\AllocationResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditAllocation extends EditRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function isEdit(): bool
    {
        return true; // Edit mode
    }

    protected function handleRecordUpdate($record, array $data): Allocation
    {
        $this->validateUniqueAllocation($data, $record->id);

        $allocation = DB::transaction(function () use ($record, $data) {
            $difference = $data['allocation'] - $record['allocation'];
            $new_available_slots = $record['balance'] + $difference;


            $record->update([
                'soft_or_commitment' => $data['soft_or_commitment'],
                'legislator_id' => $data['legislator_id'],
                'particular_id' => $data['particular_id'],
                'scholarship_program_id' => $data['scholarship_program_id'],
                'allocation' => $data['allocation'],
                'admin_cost' => $data['allocation'] * 0.02,
                'balance' => $new_available_slots,
                'year' => $data['year'],
            ]);

            return $record;
        });

        NotificationHandler::sendSuccessNotification('Saved', 'Allocation has been updated successfully.');

        return $allocation;
    }

    protected function validateUniqueAllocation(array $data, $currentId)
    {
        $allocation = Allocation::withTrashed()
            ->where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->where('id', '!=', $currentId) // Exclude current record ID
            ->first();

        if ($allocation) {
            $message = $allocation->deleted_at
                ? 'This allocation with the provided details has been deleted and must be restored before reuse.'
                : 'This Allocation with the provided details already exists.';

            throw ValidationException::withMessages([
                'error' => $message,
            ]);
        }
    }
}
