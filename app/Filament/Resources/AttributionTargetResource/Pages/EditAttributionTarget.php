<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use App\Models\Allocation;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAttributionTarget extends EditRecord
{
    protected static string $resource = AttributionTargetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.attribution-targets.edit', ['record' => $this->record->id]) => 'Attribution Target',
            'Edit'
        ];
    }

    protected ?string $heading = 'Edit Attribution Target';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $senderAllocation = $record->allocation;
        $receiverAllocation = $record->attributionAllocation;

        $data['attribution_sender'] = $senderAllocation->legislator_id ?? null;
        $data['attribution_sender_particular'] = $senderAllocation->particular_id ?? null;
        $data['attribution_scholarship_program'] = $senderAllocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $senderAllocation->year ?? null;
        $data['attribution_appropriation_type'] = $record['appropriation_type'];
        $data['attribution_receiver'] = $receiverAllocation->legislator_id ?? null;
        $data['attribution_receiver_particular'] = $receiverAllocation->particular_id ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $senderAllocation = Allocation::where('legislator_id', $data['attribution_sender'])
                ->where('particular_id', $data['attribution_sender_particular'])
                ->where('scholarship_program_id', $data['attribution_scholarship_program'])
                ->where('year', $data['allocation_year'])
                ->first();

            if (!$senderAllocation) {
                throw new \Exception('Attribution Sender Allocation not found');
            }

            $receiverAllocation = Allocation::where('legislator_id', $data['attribution_receiver']) 
                ->where('particular_id', $data['attribution_receiver_particular'])
                ->where('scholarship_program_id', $data['attribution_scholarship_program'])
                ->where('year', $data['allocation_year'])
                ->first();

            if (!$receiverAllocation) {
                throw new \Exception('Attribution Sender Allocation not found');
            }
        });
    }
}
