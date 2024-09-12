<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Models\Allocation;
use App\Filament\Resources\AllocationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class EditAllocation extends EditRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Allocation
    {
        // Validate unique Allocation before updating
        $this->validateUniqueAllocation($data, $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Allocation updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the Allocation: ' . $e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An unexpected error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        return $record;
    }

    protected function validateUniqueAllocation(array $data, $currentId)
    {
        $query = Allocation::withTrashed()
            ->where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            $message = $query->deleted_at
                ? 'An Allocation with this combination exists and is marked as deleted. Data cannot be updated.'
                : 'An Allocation with this combination already exists.';

            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'legislator_id' => $message,
            'particular_id' => $message,
            'scholarship_program_id' => $message,
            'year' => $message,
        ]);
    }
}
