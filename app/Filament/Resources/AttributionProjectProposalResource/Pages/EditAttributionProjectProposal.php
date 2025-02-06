<?php

namespace App\Filament\Resources\AttributionProjectProposalResource\Pages;

use App\Filament\Resources\AttributionProjectProposalResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\TargetHistory;
use App\Models\Tvi;
use Auth;
use Exception;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditAttributionProjectProposal extends EditRecord
{

    protected static string $resource = AttributionProjectProposalResource::class;

    protected ?string $heading = 'Edit Attribution Project Proposal';

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.project-proposal-targets.index') => 'Attribution Project Proposal',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $data['attribution_sender'] = $record->allocation->attributor_id ?? null;
        $data['attribution_sender_particular'] = $record->allocation->attributor_particular_id ?? null;
        $data['attribution_scholarship_program'] = $record->allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $record->allocation->year ?? null;
        $data['attribution_appropriation_type'] = $record['appropriation_type'];
        $data['attribution_receiver'] = $record->allocation->legislator_id ?? null;
        $data['attribution_receiver_particular'] = $record->allocation->particular_id ?? null;

        $data['per_capita_cost'] = $data['per_capita_cost'] ?? $record->total_amount / $record->number_of_slots ?? null;

        return $data;

    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $requiredFields = [
                'attribution_sender', 'attribution_sender_particular', 'attribution_scholarship_program',
                'allocation_year', 'attribution_appropriation_type', 'attribution_receiver', 'attribution_receiver_particular',
                'tvi_id', 'qualification_title_id', 'abdd_id', 'number_of_slots', 'learning_mode_id',
            ];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("The field '$field' is required.");
                }
            }

            // $senderAllocation = Allocation::where('legislator_id', $data['attribution_sender'])
            //     ->where('particular_id', $data['attribution_sender_particular'])
            //     ->where('scholarship_program_id', $data['attribution_scholarship_program'])
            //     ->where('year', $data['allocation_year'])
            //     ->first();

            // if (!$senderAllocation) {
            //     throw new Exception('Attribution Sender Allocation not found');
            // }

            // $receiverAllocation = Allocation::where('legislator_id', $data['attribution_receiver'])
            //     ->where('particular_id', $data['attribution_receiver_particular'])
            //     ->where('scholarship_program_id', $data['attribution_scholarship_program'])
            //     ->where('year', $data['allocation_year'])
            //     ->first();

            // if (!$receiverAllocation) {
            //     $receiverAllocation = Allocation::create([
            //         'soft_or_commitment' => 'Soft',
            //         'legislator_id' => $data['attribution_receiver'],
            //         'particular_id' => $data['attribution_receiver_particular'],
            //         'scholarship_program_id' => $data['attribution_scholarship_program'],
            //         'allocation' => 0,
            //         'balance' => 0,
            //         'year' => $data['allocation_year'],
            //     ]);
            // }

            $allocation = Allocation::where('attributor_id', $data['attribution_sender'])
                ->where('legislator_id', $data['attribution_receiver'])
                ->where('attributor_particular_id', $data['attribution_sender_particular'])
                ->where('particular_id', $data['attribution_receiver_particular'])
                ->where('scholarship_program_id', $data['attribution_scholarship_program'])
                ->where('soft_or_commitment', 'Commitment')
                ->where('year', $data['allocation_year'])
                ->first();

            $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
            if (!$qualificationTitle) {
                throw new Exception('Qualification Title not found');
            }

            $numberOfSlots = $data['number_of_slots'] ?? 0;

            // dd($numberOfSlots);

            $step = ScholarshipProgram::where('name', 'STEP')->first();

            $costOfToolkitPcc = $qualificationTitle->toolkits()->where('year', $data['allocation_year'])->first();
            $totalCostOfToolkit = 0;
            $totalAmount = $data['per_capita_cost'] * $numberOfSlots;

            if ($qualificationTitle->scholarship_program_id === $step->id) {
                
                if (!$costOfToolkitPcc) {
                    $this->sendErrorNotification('Please add STEP Toolkits.');
                    throw new Exception('Please add STEP Toolkits.');
                }

                $totalCostOfToolkit = $costOfToolkitPcc->price_per_toolkit * $numberOfSlots;
                $totalAmount += $totalCostOfToolkit;
            }


            $total_training_cost_pcc = $qualificationTitle->training_cost_pcc * $numberOfSlots;
            $total_cost_of_toolkit_pcc = $totalCostOfToolkit;
            $total_training_support_fund = $qualificationTitle->training_support_fund * $numberOfSlots;
            $total_assessment_fee = $qualificationTitle->assessment_fee * $numberOfSlots;
            $total_entrepreneurship_fee = $qualificationTitle->entrepreneurship_fee * $numberOfSlots;
            $total_new_normal_assisstance = $qualificationTitle->new_normal_assisstance * $numberOfSlots;
            $total_accident_insurance = $qualificationTitle->accident_insurance * $numberOfSlots;
            $total_book_allowance = $qualificationTitle->book_allowance * $numberOfSlots;
            $total_uniform_allowance = $qualificationTitle->uniform_allowance * $numberOfSlots;
            $total_misc_fee = $qualificationTitle->misc_fee * $numberOfSlots;

            // Remove admin cost
            $total_amount = $totalAmount; // Removed admin_cost

            $institution = Tvi::find($data['tvi_id']);
            if (!$institution) {
                throw new Exception('Institution not found');
            }

            if ($allocation->balance + $record->total_amount < $total_amount) {
                throw new Exception('Insufficient funds in sender allocation');
            }

            $previousSkillPrio = SkillPriority::where([
                'training_program_id' => $record->qualification_title->training_program_id,
                'province_id' => $record->tvi->district->province_id,
                'year' => $record->allocation->year,
            ]);

            if (!$previousSkillPrio) {
                $this->sendErrorNotification('Previous Skill Priority not found.');
                throw new Exception('Previous Skill Priority not found.');
            }

            $skillPriority = $this->getSkillPriority(
                $qualificationTitle->training_program_id,
                $institution->district->province_id,
                $data['allocation_year']
            );

            if (!$skillPriority) {
                $this->sendErrorNotification('Skill Priority not found.');
                throw new Exception('Skill Priority not found.');
            }

            $allocation->balance += $record->total_amount;
            $allocation->save();

            $previousSkillPrio->increment('available_slots', $record->number_of_slots);

            $record->update([
                'allocation_id' => $allocation->id,
                'tvi_id' => $institution->id,
                'tvi_name' => $institution->name,
                'municipality_id' => $institution->municipality_id,
                'district_id' => $institution->district_id,
                'qualification_title_id' => $qualificationTitle->id,
                'qualification_title_code' => $qualificationTitle->trainingProgram->code ?? null,
                'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                'delivery_mode_id' => $data['delivery_mode_id'],
                'learning_mode_id' => $data['learning_mode_id'],
                'abdd_id' => $data['abdd_id'],
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
                'appropriation_type' => $data['attribution_appropriation_type'],
                'target_status_id' => 1,
            ]);

            $allocation->balance -= $total_amount;
            $allocation->save();

            $skillPriority->decrement('available_slots', $numberOfSlots);

            TargetHistory::create([
                'target_id' => $record->id,
                'allocation_id' => $allocation->id,
                'tvi_id' => $data['tvi_id'],
                'tvi_name' => $institution->name,
                'municipality_id' => $institution->municipality_id,
                'district_id' => $institution->district_id,
                'qualification_title_id' => $qualificationTitle->id,
                'qualification_title_code' => $qualificationTitle->trainingProgram->code ?? null,
                'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code ?? null,
                'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                'delivery_mode_id' => $data['delivery_mode_id'],
                'learning_mode_id' => $data['learning_mode_id'],
                'abdd_id' => $data['abdd_id'],
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
                'appropriation_type' => $data['attribution_appropriation_type'],
                'description' => 'Target Modified',
                'user_id' => Auth::user()->id,
            ]);


            return $record;
        });
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
