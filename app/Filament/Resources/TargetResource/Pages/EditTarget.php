<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTarget extends EditRecord
{
    protected static string $resource = TargetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;
        $legislatorId = $allocation->legislator_id ?? null;
        $scholarshipId = $allocation->scholarship_program_id ?? null;

        // Set default values if not already set
        $data['legislator_id'] = $data['legislator_id'] ?? $legislatorId;
        $data['scholarship_id'] = $data['scholarship_id'] ?? $scholarshipId;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Find the allocation based on the provided data
            $allocation = Allocation::where('legislator_id', $data['legislator_id'])
                                    ->where('scholarship_program_id', $data['scholarship_id'])
                                    ->whereNull('deleted_at')
                                    ->first();

            // Check if the allocation exists
            if (!$allocation) {
                throw new \Exception('Allocation not found for the provided legislator and scholarship program.');
            }

            // Update the existing Target record
            $record->update([
                'allocation_id' => $allocation->id,
                'tvi_id' => $data['tvi_id'],
                'priority_id' => $data['priority_id'],
                'tvet_id' => $data['tvet_id'],
                'abdd_id' => $data['abdd_id'],
                'qualification_title_id' => $data['qualification_title_id'],
                'number_of_slots' => $data['number_of_slots'],
            ]);

            // Return the updated record
            return $record;
        });
    }
}
