<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Models\Allocation;
use App\Filament\Resources\AllocationResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAllocation extends CreateRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function isEdit(): bool
    {
        return false; // Edit mode
    }

    protected function handleRecordCreation(array $data): Allocation
    {
        $this->validateUniqueAllocation($data);

        $adminCost = $data['allocation'] * 0.02;
        $balance = $data['allocation'] - $adminCost;
    
        // Create the allocation record within a transaction
        $allocation = DB::transaction(fn () => Allocation::create([
            'soft_or_commitment' => $data['soft_or_commitment'],
            'legislator_id' => $data['legislator_id'],
            'particular_id' => $data['particular_id'],
            'scholarship_program_id' => $data['scholarship_program_id'],
            'allocation' => $data['allocation'],
            'admin_cost' => $adminCost,
            'balance' => $balance,
            'year' => $data['year'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Allocation has been created successfully.');

        return $allocation;
    }

    protected function validateUniqueAllocation(array $data)
    {
        $allocation = Allocation::withTrashed()
            ->where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->first();

        if ($allocation) {
            $message = $allocation->deleted_at
                ? 'This allocation with the provided details has been deleted and must be restored before reuse..'
                : 'This Allocation with the provided details already exists.';

            $this->handleValidationException($message);
        }
    }
}