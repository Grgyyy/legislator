<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\Target;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttributionTarget extends CreateRecord
{
    protected static string $resource = AttributionTargetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.attribution-targets.create') => 'Create Attribution Target',
            'Create'
        ];
    }

    protected static ?string $title = 'Create Attribution Targets';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Target
    {
        return DB::transaction(function () use ($data) {
            $targetData = $data['targets'][0] ?? null;

            if (!$targetData) {
                throw new \Exception('No Attribution Target found.');
            }

            $requiredFields = [
                'attribution_sender', 'attribution_sender_particular', 'attribution_scholarship_program', 
                'allocation_year', 'attribution_appropriation_type', 'attribution_receiver', 
                'tvi_id', 'qualification_title_id', 'abdd_id', 'number_of_slots'
            ];

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $targetData) || empty($targetData[$field])) {
                    throw new \InvalidArgumentException("The field '$field' is required.");
                }
            }

            // Find the sender allocation
            $senderAllocation = Allocation::where('legislator_id', $targetData['attribution_sender'])
                ->where('particular_id', $targetData['attribution_sender_particular'])
                ->where('scholarship_program_id', $targetData['attribution_scholarship_program'])
                ->where('year', $targetData['allocation_year'])
                ->first();

            if (!$senderAllocation) {
                throw new \Exception('Attribution Sender Allocation not found');
            }

            // Check for existing receiver allocation
            $receiverAllocation = Allocation::where('legislator_id', $targetData['attribution_receiver'])
                ->where('scholarship_program_id', $targetData['attribution_scholarship_program'])
                ->where('year', $targetData['allocation_year'])
                ->first();

            // Create receiver allocation only if it does not exist
            if (!$receiverAllocation) {
                $receiverAllocation = Allocation::create([
                    'soft_or_commitment' => 'Soft',
                    'legislator_id' => $targetData['attribution_receiver'],
                    'particular_id' => $targetData['attribution_sender_particular'],
                    'scholarship_program_id' => $targetData['attribution_scholarship_program'],
                    'allocation' => 0,
                    'balance' => 0,
                    'year' => $targetData['allocation_year'],
                ]);
            }

            $qualificationTitle = QualificationTitle::find($targetData['qualification_title_id']);

            if (!$qualificationTitle) {
                throw new \Exception('Qualification Title not found');
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

            if($senderAllocation->balance >= $total_amount) {
                $target = Target::create([
                    'allocation_id' => $senderAllocation->id,
                    'attribution_allocation_id' => $receiverAllocation->id,
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
                    'appropriation_type' => $targetData['attribution_appropriation_type'],
                    'target_status_id' => 1,
                ]);

                $senderAllocation->balance -= $total_amount;
                $senderAllocation->attribution_sent += $total_amount;
                $senderAllocation->save();

                $receiverAllocation->attribution_received += $total_amount;
                $receiverAllocation->save();

                return $target;
            } else {
                throw new \Exception('Insufficient balance for allocation.');
            }
        });
    }

}
