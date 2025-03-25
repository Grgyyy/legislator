<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use App\Models\Allocation;
use App\Models\Particular;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditAllocation extends EditRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    public function isEdit(): bool
    {
        return true;
    }

    protected function handleRecordUpdate($record, array $data): Allocation
    {
        $this->validateUniqueAllocation($data, $record->id);

        $allocation = DB::transaction(function () use ($record, $data) {

            // $previousAllocation = (float) $record['allocation'];
            // $newAllocation = (float) $data['allocation'];
            // $newAllowableAllocation = (float) $data['allocation'] - ($data['allocation'] * 0.02);
            // $previousAdminCost = (float) $record['admin_cost'];

            // $usedSlots = $previousAllocation - $previousAdminCost;

            // $newBalance =  $newAllowableAllocation - $usedSlots;

            $previousAllocation = (float) $record['allocation'];
            $previousAdminCost = (float) $record['admin_cost'];
            $previousAllowableAllocation = (float) $previousAllocation - $previousAdminCost;
            $previousBalance = (float) $record['balance'];
            $consumedAllocation = (float) $previousAllowableAllocation - $previousBalance;

            $newAllocation = (float) $data['allocation'];
            $newAdminCost = (float) $data['allocation'] * 0.02;
            $newAllowableAllocation = (float) $newAllocation - $newAdminCost;
            $newBalance = (float) $newAllowableAllocation - $consumedAllocation;


            $record->update([
                'soft_or_commitment' => $data['soft_or_commitment'],
                'legislator_id' => $data['legislator_id'],
                'attributor_id' => $data['attributor_id'] ?? null,
                'particular_id' => $data['particular_id'],
                'attributor_particular_id' => $data['attributor_particular_id'] ?? null,
                'scholarship_program_id' => $data['scholarship_program_id'],
                'allocation' => $newAllocation,
                'admin_cost' => $newAdminCost,
                'balance' => $newBalance,
                'year' => $data['year'],
            ]);

            return $record;
        });

        NotificationHandler::sendSuccessNotification('Saved', 'Allocation has been updated successfully.');

        return $allocation;
    }

    protected function afterSave(): void
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->record)
            ->event('Updated')
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
                ? "An attribution allocation for '{$this->record->legislator->name}' has been updated, attributed by '{$this->record->attributor->name}'."
                : "An allocation for '{$this->record->legislator->name}' has been successfully updated."
            );
    }

    protected function removeLeadingZeros($value)
    {
        return ltrim($value, '0') ?: '0';
    }

    protected function validateUniqueAllocation(array $data, $currentId)
    {
        $attributorParticular = $data['attributor_particular_id'] ?? null;

        $allocation = Allocation::withTrashed()
            ->where('soft_or_commitment', $data['soft_or_commitment'])
            ->where('legislator_id', $data['legislator_id'])
            ->where('attributor_id', $data['attributor_id'] ?? null)
            ->where('attributor_particular_id', $attributorParticular)
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->where('id', '!=', $currentId)
            ->first();

        if ($allocation) {
            $message = $allocation->deleted_at
                ? 'This allocation with the provided details has been deleted and must be restored before reuse.'
                : 'This allocation with the provided details already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if ($attributorParticular) {
            $particular = Particular::find($data['attributor_particular_id']);

            if (!$particular) {
                throw new \Exception("The 'attributor_particular_id' does not exist in the 'particulars' table.");
            }
        }
    }
}
