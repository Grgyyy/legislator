<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\Target;
use App\Models\TargetHistory;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateTarget extends CreateRecord
{
    protected static string $resource = TargetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected ?string $heading = 'Create Pending Targets';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.create') => 'Pending Targets',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): Target
    {
        return DB::transaction(function () use ($data) {
            $targetData = $data['targets'][0] ?? null;

            if (!$targetData) {
                throw new \Exception('No target data found.');
            }

            $requiredFields = [
                'allocation_legislator_id',
                'particular_id',
                'scholarship_program_id',
                'qualification_title_id',
                'number_of_slots',
                'tvi_id',
                'appropriation_type',
            ];

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $targetData) || empty($targetData[$field])) {
                    throw new \InvalidArgumentException("The field '$field' is required.");
                }
            }

            $attributedLegislator = Legislator::find($targetData['legislator_id']);
            $attributedLegislatorParticular = Particular::find($targetData['attribution_particular_id']);

            $attributedAllocation = $attributedLegislator && $attributedLegislatorParticular
                ? Allocation::where('legislator_id', $attributedLegislator->id)
                    ->where('particular_id', $attributedLegislatorParticular->id)
                    ->where('scholarship_program_id', $targetData['scholarship_program_id'])
                    ->where('year', $targetData['allocation_year'])
                    ->first()
                : null;

            $allocation = Allocation::where('legislator_id', $targetData['allocation_legislator_id'])
                ->where('particular_id', $targetData['particular_id'])
                ->where('scholarship_program_id', $targetData['scholarship_program_id'])
                ->where('year', $targetData['allocation_year'])
                ->first();

            if (!$allocation) {
                throw new \Exception('Primary Allocation not found.');
            }

            $qualificationTitle = QualificationTitle::find($targetData['qualification_title_id']);
            if (!$qualificationTitle) {
                throw new \Exception('Qualification Title not found.');
            }

            $numberOfSlots = $targetData['number_of_slots'] ?? 0;

            $total_amount = $qualificationTitle->pcc * $numberOfSlots;

            if ($allocation->balance >= $total_amount) {
                $target = Target::create([
                    'legislator_id' => $attributedLegislator->id ?? null,
                    'allocation_id' => $allocation->id,
                    'tvi_id' => $targetData['tvi_id'],
                    'qualification_title_id' => $qualificationTitle->id,
                    'abdd_id' => $targetData['abdd_id'],
                    'number_of_slots' => $numberOfSlots,
                    'total_amount' => $total_amount,
                    'appropriation_type' => $targetData['appropriation_type'],
                    'target_status_id' => 1,
                ]);

                if ($attributedLegislator) {
                    if ($attributedAllocation) {
                        $attributedAllocation->balance -= $total_amount;
                        $attributedAllocation->attribution_sent += $total_amount;
                        $attributedAllocation->save();
                    }
                } else {
                    $allocation->balance -= $total_amount;
                    $allocation->save();
                }

                if ($attributedLegislator) {
                    $allocation->attribution_received += $total_amount;
                    $allocation->save();
                }

                TargetHistory::create([
                    'target_id' => $target->id,
                    'allocation_id' => $allocation->id,
                    'tvi_id' => $targetData['tvi_id'],
                    'qualification_title_id' => $qualificationTitle->id,
                    'abdd_id' => $targetData['abdd_id'],
                    'number_of_slots' => $numberOfSlots,
                    'total_amount' => $total_amount,
                    'appropriation_type' => $targetData['appropriation_type'],
                    'description' => 'Target Created',
                ]);

                return $target;
            } else {
                throw new \Exception('Insufficient balance for allocation.');
            }
        });
    }
}
