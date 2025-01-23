<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use App\Models\Allocation;
use App\Models\ProvinceAbdd;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\Tvi;
use Exception;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateAttributionTarget extends CreateRecord
{
    protected static string $resource = AttributionTargetResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected static ?string $title = 'Create Attribution Target';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/attribution-targets' => 'Attribution Targets',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): Target
    {
        return DB::transaction(function () use ($data) {
            $targetData = $data['targets'][0] ?? null;

            if (!$targetData) {
                throw new Exception('No Attribution Target found.');
            }

            $requiredFields = [
                'attribution_sender', 'attribution_sender_particular', 'attribution_scholarship_program',
                'allocation_year', 'attribution_appropriation_type', 'attribution_receiver', 'attribution_receiver_particular',
                'tvi_id', 'qualification_title_id', 'abdd_id', 'number_of_slots', 'learning_mode_id',
            ];

            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $targetData) || empty($targetData[$field])) {
                    throw new \InvalidArgumentException("The field '$field' is required.");
                }
            }

            // // Fetch sender allocation
            // $senderAllocation = Allocation::where('legislator_id', $targetData['attribution_sender'])
            //     ->where('particular_id', $targetData['attribution_sender_particular'])
            //     ->where('scholarship_program_id', $targetData['attribution_scholarship_program'])
            //     ->where('year', $targetData['allocation_year'])
            //     ->first();

            // if (!$senderAllocation) {
            //     throw new Exception('Attribution Sender Allocation not found');
            // }

            // // Fetch or create receiver allocation
            // $receiverAllocation = Allocation::where('legislator_id', $targetData['attribution_receiver'])
            //     ->where('particular_id', $targetData['attribution_receiver_particular']) // FIXED FIELD
            //     ->where('scholarship_program_id', $targetData['attribution_scholarship_program'])
            //     ->where('year', $targetData['allocation_year'])
            //     ->first();

            // if (!$receiverAllocation) {
            //     $receiverAllocation = Allocation::create([
            //         'soft_or_commitment' => 'Soft',
            //         'legislator_id' => $targetData['attribution_receiver'],
            //         'particular_id' => $targetData['attribution_receiver_particular'], // FIXED FIELD
            //         'scholarship_program_id' => $targetData['attribution_scholarship_program'],
            //         'allocation' => 0,
            //         'balance' => 0,
            //         'year' => $targetData['allocation_year'],
            //     ]);
            // }

            $allocation = Allocation::where('attributor_id', $targetData['attribution_sender'])
                ->where('legislator_id', $targetData['attribution_receiver'])
                ->where('attributor_particular_id', $targetData['attribution_sender_particular'])
                ->where('particular_id', $targetData['attribution_receiver_particular'])
                ->where('scholarship_program_id', $targetData['attribution_scholarship_program'])
                ->where('year', $targetData['allocation_year'])
                ->first();
            
            if (!$allocation) {
                throw new Exception('Allocation not found');
            }

            $qualificationTitle = QualificationTitle::find($targetData['qualification_title_id']);
            if (!$qualificationTitle) {
                throw new Exception('Qualification Title not found');
            }

            $numberOfSlots = $targetData['number_of_slots'] ?? 0;

            $step = ScholarshipProgram::where('name', 'STEP')->first();

            $costOfToolkitPcc = $qualificationTitle->toolkits()->where('year', $targetData['allocation_year'])->first();
            $totalCostOfToolkit = 0;
            $totalAmount = $qualificationTitle->pcc * $numberOfSlots;


            if ($qualificationTitle->scholarship_program_id === $step->id) {
                $totalCostOfToolkit = $costOfToolkitPcc->price_per_toolkit * $numberOfSlots;
                $totalAmount += $totalCostOfToolkit;
            }

            $total_training_cost_pcc = $qualificationTitle->training_cost_pcc * $numberOfSlots;
            $total_cost_of_toolkit_pcc = $totalCostOfToolkit;
            $total_training_support_fund = $qualificationTitle->training_support_fund * $numberOfSlots;
            $total_assessment_fee = $qualificationTitle->assessment_fee * $numberOfSlots;
            $total_entrepreneurship_fee = $qualificationTitle->entrepreneurship_fee * $numberOfSlots;
            $total_new_normal_assisstance = $qualificationTitle->new_normal_assistance * $numberOfSlots;
            $total_accident_insurance = $qualificationTitle->accident_insurance * $numberOfSlots;
            $total_book_allowance = $qualificationTitle->book_allowance * $numberOfSlots;
            $total_uniform_allowance = $qualificationTitle->uniform_allowance * $numberOfSlots;
            $total_misc_fee = $qualificationTitle->misc_fee * $numberOfSlots;

            // Remove admin cost
            $total_amount = $totalAmount; // Removed admin_cost

            $institution = Tvi::find($targetData['tvi_id']);
            if (!$institution) {
                throw new Exception('Institution not found');
            }

            // Check for sufficient balance in the sender's allocation
            if ($allocation->balance < $total_amount) {
                throw new Exception('Insufficient funds in sender allocation');
            }

            // Check for available slots in ProvinceAbdd
            // $provinceAbdd = $this->getProvinceAbdd(
            //     $targetData['abdd_id'],
            //     $institution->district->province_id,
            //     $targetData['allocation_year']
            // );

            // if (!$provinceAbdd) {
            //     throw new Exception('ProvinceAbdd entry not found');
            // }

            $skillPriority = $this->getSkillPriority(
                $qualificationTitle->training_program_id,
                $institution->district->province_id,
                $targetData['allocation_year']
            );

            if (!$skillPriority) {
                throw new Exception('Skill Priority not found');
            }

            if ($skillPriority->available_slots < $numberOfSlots) {
                throw new Exception('Not enough available slots in Skill Priority.');
            }

            // If both conditions are met, proceed with creation
            $target = Target::create([
                'allocation_id' => $allocation->id,
                'tvi_id' => $institution->id,
                'tvi_name' => $institution->name,
                'municipality_id' => $institution->municipality_id,
                'district_id' => $institution->district_id,
                'qualification_title_id' => $qualificationTitle->id,
                'qualification_title_code' => $qualificationTitle->trainingProgram->code ?? null,
                'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code ?? null,
                'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                'delivery_mode_id' => $targetData['delivery_mode_id'],
                'learning_mode_id' => $targetData['learning_mode_id'],
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
                'total_amount' => $total_amount, // Removed admin_cost
                'appropriation_type' => $targetData['attribution_appropriation_type'],
                'target_status_id' => 1,
            ]);

            // Update sender and receiver balances
            $allocation->balance -= $total_amount;
            $allocation->save();


            // $provinceAbdd->decrement('available_slots', $numberOfSlots);
            $skillPriority->decrement('available_slots', $numberOfSlots);


            // Log the creation in TargetHistory
            TargetHistory::create([
                'target_id' => $target->id,
                'allocation_id' => $allocation->id,
                'tvi_id' => $targetData['tvi_id'],
                'tvi_name' => $institution->name,
                'municipality_id' => $institution->municipality_id,
                'district_id' => $institution->district_id,
                'qualification_title_id' => $qualificationTitle->id,
                'qualification_title_code' => $qualificationTitle->trainingProgram->code ?? null,
                'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code ?? null,
                'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                'delivery_mode_id' => $targetData['delivery_mode_id'],
                'learning_mode_id' => $targetData['learning_mode_id'],
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
                'total_amount' => $total_amount, // Removed admin_cost
                'appropriation_type' => $targetData['attribution_appropriation_type'],
                'description' => 'Target Created',
                'user_id' => Auth::user()->id,
            ]);

            return $target;
        });
    }

    private function getProvinceAbdd(int $abddId, int $provinceId, int $appropriationYear): ProvinceAbdd
    {
        $provinceAbdd = ProvinceAbdd::where([
            'abdd_id' => $abddId,
            'province_id' => $provinceId,
            'year' => $appropriationYear,
        ])->first();

        if (!$provinceAbdd || $provinceAbdd->available_slots <= 0) {
            throw new Exception('ProvinceAbdd entry unavailable or no slots left.');
        }

        return $provinceAbdd;
    }

    private function getSkillPriority(int $trainingProgram, int $provinceId, int $appropriationYear): SkillPriority
    {
        $skillPriority = SkillPriority::where([
            'training_program_id' => $trainingProgram,
            'province_id' => $provinceId,
            'year' => $appropriationYear,
        ])->first();

        if (!$skillPriority) {
            $this->sendErrorNotification('Skill Priority not found.');
            throw new Exception('Skill Priority not found.');
        }

        if ($skillPriority->available_slots <= 0) {
            $this->sendErrorNotification('No available slots in Skill Priority');
            throw new Exception('No available slots in Skill Priority.');
        }

        return $skillPriority;
    }
}
