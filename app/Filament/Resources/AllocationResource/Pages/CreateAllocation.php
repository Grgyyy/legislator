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

    /**
     * Get the URL to redirect to after creating a record.
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Indicates if the current operation is in edit mode.
     *
     * @return bool
     */
    public function isEdit(): bool
    {
        return false;
    }

    /**
     * Handle the creation of a new allocation record.
     *
     * @param array $data
     * @return Allocation
     * @throws \Throwable
     */
    protected function handleRecordCreation(array $data): Allocation
    {
        $this->validateUniqueAllocation($data);

        $adminCost = $this->calculateAdminCost($data['allocation']);
        $balance = $this->calculateBalance($data['allocation'], $adminCost);

        $allocation = DB::transaction(function () use ($data, $adminCost, $balance) {
            return Allocation::create([
                'soft_or_commitment' => $data['soft_or_commitment'],
                'legislator_id' => $data['legislator_id'],
                'attributor_id' => $data['attributor_id'],
                'particular_id' => $data['particular_id'],
                'scholarship_program_id' => $data['scholarship_program_id'],
                'allocation' => $data['allocation'],
                'admin_cost' => $adminCost,
                'balance' => $balance,
                'year' => $data['year'],
            ]);
        });

        // Send success notification
        NotificationHandler::sendSuccessNotification('Created', 'Allocation has been created successfully.');

        return $allocation;
    }

    /**
     * Validate that the allocation details are unique.
     *
     * @param array $data
     * @return void
     */
    protected function validateUniqueAllocation(array $data): void
    {
        $allocation = Allocation::where('soft_or_commitment', $data['soft_or_commitment'])
            ->where('legislator_id', $data['legislator_id'])
            ->where('attributor_id', $data['attributor_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->first();

        if ($allocation) {
            $message = $allocation->trashed()
                ? 'This allocation with the provided details has been deleted. Please restore it before reuse.'
                : 'This allocation with the provided details already exists.';

            $this->handleValidationException($message);
        }
    }

    /**
     * Calculate the administrative cost as 2% of the allocation.
     *
     * @param float $allocation
     * @return float
     */
    protected function calculateAdminCost(float $allocation): float
    {
        return $allocation * 0.02;
    }

    /**
     * Calculate the balance by subtracting the admin cost from the allocation.
     *
     * @param float $allocation
     * @param float $adminCost
     * @return float
     */
    protected function calculateBalance(float $allocation, float $adminCost): float
    {
        return $allocation - $adminCost;
    }

    /**
     * Handle validation exception by throwing a custom validation error.
     *
     * @param string $message
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function handleValidationException(string $message): void
    {
        NotificationHandler::handleValidationException('Something went wrong', $message);
    }
}
