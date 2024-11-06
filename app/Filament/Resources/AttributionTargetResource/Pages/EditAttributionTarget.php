<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
}
