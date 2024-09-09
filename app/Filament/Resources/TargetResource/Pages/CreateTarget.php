<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use App\Models\Target;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateTarget extends CreateRecord
{
    protected static string $resource = TargetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Target
    {
        return DB::transaction(function () use ($data) {

            $allocation = Allocation::where('legislator_id', $data['legislator_id'])
                                        ->where('scholarship_program_id', $data['scholarship_id'])
                                        ->whereNull('deleted_at')
                                        ->first();

            // Create a new Target record
            $target = Target::create([
                'allocation_id' => $allocation->id,
                'tvi_id' => $data['tvi_id'],
                'priority_id' => $data['priority_id'],
                'tvet_id' => $data['tvet_id'],
                'abdd_id' => $data['abdd_id'],
                'qualification_title_id' => $data['qualification_title_id'],
                'number_of_slots' => $data['number_of_slots'],
            ]);

            // Return the newly created Target model
            return $target;
        });
    }
}
