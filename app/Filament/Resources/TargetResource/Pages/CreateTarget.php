<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use App\Models\Legislator;
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

            $attributedLegislator = Legislator::where('id', $targetData['legislator_id'])->first();

            $allocation = Allocation::where('legislator_id', $targetData['allocation_legislator_id'])
                ->where('particular_id', $targetData['particular_id'])
                ->where('scholarship_program_id', $targetData['scholarship_program_id'])
                ->where('year', $targetData['allocation_year'])
                ->first();

            if (!$allocation) {
                throw new \Exception('Allocation not found.');
            }

            $qualificationTitle = QualificationTitle::find($targetData['qualification_title_id']);
            if (!$qualificationTitle) {
                throw new \Exception('Qualification Title not found.');
            }

            $numberOfSlots = $targetData['number_of_slots'] ?? 0;

            $total_training_cost_pcc = $qualificationTitle->training_cost_pcc * $numberOfSlots;
            $total_cost_of_toolkit_pcc = $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots;
            $total_training_support_fund = $qualificationTitle->training_support_fund * $numberOfSlots;
            $total_assessment_fee = $qualificationTitle->assessment_fee * $numberOfSlots;
            $total_entrepreneurship_fee = $qualificationTitle->entrepreneurship_fee * $numberOfSlots;
            $total_new_normal_assisstance = $qualificationTitle->new_normal_assisstance * $numberOfSlots;
            $total_accident_insurance = $qualificationTitle->accident_insurance * $numberOfSlots;
            $total_book_allowance = $qualificationTitle->book_allowance * $numberOfSlots;
            $total_uniform_allowance = $qualificationTitle->uniform_allowance * $numberOfSlots;
            $total_misc_fee = $qualificationTitle->misc_fee * $numberOfSlots;
            $total_amount = $qualificationTitle->pcc * $numberOfSlots;

            // Check allocation balance
            if ($allocation->balance >= $total_amount) {
                // Create target
                $target = Target::create([
                    'allocation_id' => $allocation->id,
                    'tvi_id' => $targetData['tvi_id'],
                    'qualification_title_id' => $qualificationTitle->id,
                    'abdd_id' => $targetData['abdd_id'],
                    'number_of_slots' => $numberOfSlots,
                    'total_training_cost_pcc' => $total_training_cost_pcc,
                    'total_cost_of_toolkit_pcc' => $total_cost_of_toolkit_pcc,
                    'total_training_support_fund' => $total_training_support_fund,
                    'total_assessment_fee' => $total_assessment_fee,
                    'total_entrepreneurship_fee' => $total_entrepreneurship_fee,
                    'total_new_normal_assisstance' => $total_new_normal_assisstance,
                    'total_accident_insurance' => $total_accident_insurance,
                    'total_book_allowance' => $total_book_allowance,
                    'total_uniform_allowance' => $total_uniform_allowance,
                    'total_misc_fee' => $total_misc_fee,
                    'total_amount' => $total_amount,
                    'appropriation_type' => $targetData['appropriation_type'],
                    'target_status_id' => 1,
                    'legislator_id' => $attributedLegislator->id ?? null,
                ]);

                $allocation->balance -= $total_amount;

                if ($attributedLegislator) {
                    $allocation->attribution_sent = $total_amount;
                }
                $allocation->save();

                TargetHistory::create([
                    'target_id' => $target->id,
                    'allocation_id' => $allocation->id,
                    'tvi_id' => $targetData['tvi_id'],
                    'qualification_title_id' => $qualificationTitle->id,
                    'abdd_id' => $targetData['abdd_id'],
                    'number_of_slots' => $numberOfSlots,
                    'total_training_cost_pcc' => $total_training_cost_pcc,
                    'total_cost_of_toolkit_pcc' => $total_cost_of_toolkit_pcc,
                    'total_training_support_fund' => $total_training_support_fund,
                    'total_assessment_fee' => $total_assessment_fee,
                    'total_entrepreneurship_fee' => $total_entrepreneurship_fee,
                    'total_new_normal_assisstance' => $total_new_normal_assisstance,
                    'total_accident_insurance' => $total_accident_insurance,
                    'total_book_allowance' => $total_book_allowance,
                    'total_uniform_allowance' => $total_uniform_allowance,
                    'total_misc_fee' => $total_misc_fee,
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
