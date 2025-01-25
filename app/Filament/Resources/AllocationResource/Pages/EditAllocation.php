<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Models\Allocation;
use App\Filament\Resources\AllocationResource;
use App\Models\Particular;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
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
            $deduction = $difference * 0.02;
            $new_balance = $record['balance'] + ($difference - $deduction);


            $record->update([
                'soft_or_commitment' => $data['soft_or_commitment'],
                'legislator_id' => $data['legislator_id'],
                'attributor_id' => $data['attributor_id'],
                'particular_id' => $data['particular_id'],
                'attributor_particular_id' => $data['attributor_particular_id'],
                'scholarship_program_id' => $data['scholarship_program_id'],
                'allocation' => $data['allocation'],
                'admin_cost' => $data['allocation'] * 0.02,
                'balance' => $new_balance,
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
            ->where('soft_or_commitment', $data['soft_or_commitment'])
            ->where('legislator_id', $data['legislator_id'])
            ->where('attributor_id', $data['attributor_id'])
            ->where('attributor_particular_id', $data['attributor_particular_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->where('id', '!=', $currentId) 
            ->first();

        if ($allocation) {
            $message = $allocation->deleted_at
                ? 'This allocation with the provided details has been deleted and must be restored before reuse.'
                : 'This Allocation with the provided details already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if ($data['attributor_particular_id']) {
            $particular = Particular::find($data['attributor_particular_id']);

            if (!$particular) {
                throw new \Exception("The 'attributor_particular_id' does not exist in the 'particulars' table.");
            }
        }
    }
}
