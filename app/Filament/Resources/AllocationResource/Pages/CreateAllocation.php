<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use App\Models\Allocation;
use App\Models\Particular;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAllocation extends CreateRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function isEdit(): bool
    {
        return false;
    }

    protected function handleRecordCreation(array $data): Allocation
    {
        $this->validateUniqueAllocation($data);

        // Validate that the 'attributor_particular_id' exists in the 'particulars' table if provided
        $this->validateAttributorParticularId($data['attributor_particular_id'] ?? null);

        $adminCost = $this->calculateAdminCost($data['allocation']);
        $balance = $this->calculateBalance($data['allocation'], $adminCost);

        $allocation = DB::transaction(function () use ($data, $adminCost, $balance) {
            return Allocation::create([
                'soft_or_commitment' => $data['soft_or_commitment'],
                'legislator_id' => $data['legislator_id'],
                'attributor_id' => $data['attributor_id'] ?? null,
                'particular_id' => $data['particular_id'],
                'attributor_particular_id' => $data['attributor_particular_id'] ?? null,
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

    protected function afterCreate(): void
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->record)
            ->event('Created') // Set the event type
            ->withProperties([
                'soft_or_commitment' => $this->record->soft_or_commitment,
                'legislator' => $this->record->legislator->name,
                'attributor' => $this->record->attributor->name ?? null,
                'particular' => $this->record->particular_id,
                'attributor_particular' => $this->record->attributor_particular_id,
                'scholarship_program' => $this->record->scholarship_program->name,
                'allocation' => $this->removeLeadingZeros($this->record->allocation),
                'admin_cost' => $this->removeLeadingZeros($this->record->admin_cost),
                'balance' => $this->removeLeadingZeros($this->record->balance),
                'year' => $this->record->year,
            ])
            ->log(
                $this->record->attributor
                    ? "An Attribution Allocation for '{$this->record->legislator->name}' has been created, attributed by '{$this->record->attributor->name}'."
                    : "An Allocation for '{$this->record->legislator->name}' has been successfully created."
            );
    }

    protected function removeLeadingZeros($value)
    {
        return ltrim($value, '0') ?: '0';
    }

    protected function validateAttributorParticularId($attributorParticularId): void
    {
        if ($attributorParticularId) {
            $particular = Particular::find($attributorParticularId);

            if (!$particular) {
                throw new \Exception("The 'attributor_particular_id' does not exist in the 'particulars' table.");
            }
        }
    }

    /**
     * Validate that the allocation details are unique.
     *
     * @param array $data
     * @return void
     */
    protected function validateUniqueAllocation(array $data): void
    {
        $query = Allocation::where('soft_or_commitment', $data['soft_or_commitment'])
            ->where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year']);

        // Apply optional filters only if values are present
        if (!empty($data['attributor_id'])) {
            $query->where('attributor_id', $data['attributor_id']);
        }

        if (!empty($data['attributor_particular_id'])) {
            $query->where('attributor_particular_id', $data['attributor_particular_id']);
        }

        $allocation = $query->first();

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
