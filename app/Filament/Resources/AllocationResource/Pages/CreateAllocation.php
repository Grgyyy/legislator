<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use App\Models\Allocation;
use App\Models\Particular;
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
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

    public function isEdit(): bool
    {
        return false;
    }

    protected function handleRecordCreation(array $data): Allocation
    {
        $this->validateUniqueAllocation($data);

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

        NotificationHandler::sendSuccessNotification('Created', 'Allocation has been created successfully.');

        return $allocation;
    }

    protected function afterCreate(): void
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->record)
            ->event('Created')
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
                ? "An attribution allocation for '{$this->record->legislator->name}' has been created, attributed by '{$this->record->attributor->name}'."
                : "An allocation for '{$this->record->legislator->name}' has been successfully created."
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
                throw new \Exception("The 'attributor_particular_id does not exist in the 'particulars' table.");
            }
        }
    }

    protected function validateUniqueAllocation(array $data): void
    {
        $query = Allocation::where('soft_or_commitment', $data['soft_or_commitment'])
            ->where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year']);

        if (!empty($data['attributor_id'])) {
            $query->where('attributor_id', $data['attributor_id']);
        }

        if (!empty($data['attributor_particular_id'])) {
            $query->where('attributor_particular_id', $data['attributor_particular_id']);
        }

        $allocation = $query->first();

        if ($allocation) {
            $message = $allocation->trashed()
                ? 'This allocation with the provided details has been deleted and must be restored before reuse.'
                : 'This allocation with the provided details already exists.';

            $this->handleValidationException($message);
        }
    }

    protected function calculateAdminCost(float $allocation): float
    {
        return $allocation * 0.02;
    }

    protected function calculateBalance(float $allocation, float $adminCost): float
    {
        return $allocation - $adminCost;
    }

    protected function handleValidationException(string $message): void
    {
        NotificationHandler::handleValidationException('Something went wrong', $message);
    }
}
