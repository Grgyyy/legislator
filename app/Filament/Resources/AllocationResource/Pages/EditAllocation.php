<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use App\Models\Allocation;
use App\Models\Particular;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use function PHPUnit\Framework\isEmpty;
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
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }
    
    public function isEdit(): bool
    {
        return true; // Edit mode
    }

    protected function handleRecordUpdate($record, array $data): Allocation
    {
        $this->validateUniqueAllocation($data, $record->id);

        $allocation = DB::transaction(function () use ($record, $data) {
            
            $previousAllocation = (float) $record['allocation'];
            $newAllocation = (float) $data['allocation'];
            $previousBalance = (float) $record['balance'];

            $usedSlots = $previousAllocation - $previousBalance;

            $newBalance = max(0, $newAllocation - $usedSlots);

            $record->update([
                'soft_or_commitment' => $data['soft_or_commitment'],
                'legislator_id' => $data['legislator_id'],
                'attributor_id' => $data['attributor_id'] ?? null,
                'particular_id' => $data['particular_id'],
                'attributor_particular_id' => $data['attributor_particular_id'] ?? null,
                'scholarship_program_id' => $data['scholarship_program_id'],
                'allocation' => $newAllocation, 
                'admin_cost' => $newAllocation * 0.02, 
                'balance' => $newBalance, 
                'year' => $data['year'],
            ]);

            return $record;
        });

        NotificationHandler::sendSuccessNotification('Saved', 'Allocation has been updated successfully.');

        return $allocation;
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
                : 'This Allocation with the provided details already exists.';

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
