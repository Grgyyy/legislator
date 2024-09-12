<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Models\Allocation;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\AllocationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class CreateAllocation extends CreateRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Allocation
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueAllocation($data);

            return Allocation::create([
                'legislator_id' => $data['legislator_id'],
                'particular_id' => $data['particular_id'],
                'scholarship_program_id' => $data['scholarship_program_id'],
                'allocation' => $data['allocation'],
                'admin_cost' => $data['admin_cost'],
                'balance' => $data['balance'],
                'year' => $data['year'],
            ]);
        });
    }

    protected function validateUniqueAllocation(array $data)
    {
        $existingAllocation = Allocation::withTrashed()
            ->where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['year'])
            ->first();

        if ($existingAllocation) {
            $message = $existingAllocation->deleted_at
                ? 'An Allocation with this combination exists and is marked as deleted. Data cannot be created.'
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
