<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
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

            \Log::info('Query Data:', $data);

            // Validate and fetch allocation
            $allocation = Allocation::where('legislator_id', $data['legislator_id'])
                ->where('particular_id', $data['particular_id'])
                ->where('scholarship_program_id', $data['scholarship_program_id'])
                ->first();

            if (!$allocation) {
                \Log::error('Allocation not found', $data);
                throw new \Exception('Allocation not found');
            }

            // Validate and fetch qualification title
            $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);

            if (!$qualificationTitle) {
                \Log::error('Qualification Title not found', $data);
                throw new \Exception('Qualification Title not found');
            }

            // Initialize number of slots and compute total costs
            $numberOfSlots = $data['number_of_slots'] ?? 0;

            $total_training_cost_pcc = $qualificationTitle->training_cost_pcc * $numberOfSlots;
            $total_cost_of_toolkit_pcc = $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots;
            $total_training_support_fund = $qualificationTitle->training_support_fund * $numberOfSlots;
            $total_assessment_fee = $qualificationTitle->assessment_fee * $numberOfSlots;
            $total_entrepeneurship_fee = $qualificationTitle->entrepeneurship_fee * $numberOfSlots;
            $total_new_normal_assisstance = $qualificationTitle->new_normal_assisstance * $numberOfSlots;
            $total_accident_insurance = $qualificationTitle->accident_insurance * $numberOfSlots;
            $total_book_allowance = $qualificationTitle->book_allowance * $numberOfSlots;
            $total_uniform_allowance = $qualificationTitle->uniform_allowance * $numberOfSlots;
            $total_misc_fee = $qualificationTitle->misc_fee * $numberOfSlots;
            $total_amount = $qualificationTitle->pcc * $numberOfSlots;

            // Create and return the new Target record
            $target = Target::create([
                'allocation_type' => $data['allocation_type'],
                'allocation_id' => $allocation->id,
                'tvi_id' => $data['tvi_id'],
                'abdd_id' => $data['abdd_id'],
                'qualification_title_id' => $qualificationTitle->id,
                'number_of_slots' => $numberOfSlots,
                'total_training_cost_pcc' => $total_training_cost_pcc,
                'total_cost_of_toolkit_pcc' => $total_cost_of_toolkit_pcc,
                'total_training_support_fund' => $total_training_support_fund,
                'total_assessment_fee' => $total_assessment_fee,
                'total_entrepeneurship_fee' => $total_entrepeneurship_fee,
                'total_new_normal_assisstance' => $total_new_normal_assisstance,
                'total_accident_insurance' => $total_accident_insurance,
                'total_book_allowance' => $total_book_allowance,
                'total_uniform_allowance' => $total_uniform_allowance,
                'total_misc_fee' => $total_misc_fee,
                'total_amount' => $total_amount,
                'appropriation_type' => $data['appropriation_type'],
                'target_status_id' => 1
            ]);

            return $target;
        });
    }
}
