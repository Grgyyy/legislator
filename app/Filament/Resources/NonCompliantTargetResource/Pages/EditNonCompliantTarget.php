<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\ProvinceAbdd;
use App\Models\QualificationScholarship;
use App\Models\QualificationTitle;
use App\Models\SkillPriority;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use App\Models\Tvi;
use Auth;
use DB;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditNonCompliantTarget extends EditRecord
{
    protected static string $resource = NonCompliantTargetResource::class;

    protected ?string $heading = 'Edit Non-Compliant Targets';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.non-compliant-targets.index') => 'Non-Compliant Targets',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->record;
        $attributionAllocation = $record->attribution_allocation_id;

        if ($attributionAllocation) {
            return route('filament.admin.resources.attribution-targets.index');
        } else {
            return route('filament.admin.resources.targets.index');
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $data['sender_legislator_id'] = $record->allocation->attributor_id ?? null;
        $data['sender_particular_id'] = $record->allocation->attributor_particular_id ?? null;

        $data['receiver_legislator_id'] = $record->allocation->legislator_id ?? null;
        $data['receiver_particular_id'] = $record->allocation->particular_id;

        $data['scholarship_program_id'] = $record->allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $record->allocation->year ?? null;
        $data['target_id'] = $record->id ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return DB::transaction(function () use ($record, $data) {
                $senderLegislatorId = $record->allocation->attributor_id ?? null;
                $senderParticularId = $record->allocation->attributor_particular_id ?? null;
                $receiverLegislatorId = $record->allocation->legislator_id;
                $receiverParticularId = $record->allocation->particular_id;

                if (is_null($receiverParticularId)) {
                    throw new \Exception('Receiver Particular ID cannot be null');
                }

                // $receiverAllocation = Allocation::where('legislator_id', $receiverLegislatorId)
                //     ->where('particular_id', $receiverParticularId)
                //     ->where('scholarship_program_id', $data['scholarship_program_id'] ?? null)
                //     ->where('year', $data['allocation_year'] ?? null)
                //     ->first();

                // if (!$receiverAllocation) {
                //     throw new \Exception('Receiver Particular ID cannot be null');
                // }

                // $senderAllocation = Allocation::where('legislator_id', $senderLegislatorId)
                //     ->where('particular_id', $senderParticularId)
                //     ->where('scholarship_program_id', $data['scholarship_program_id'] ?? null)
                //     ->where('year', $data['allocation_year'] ?? null)
                //     ->first();

                $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
                if (!$qualificationTitle) {
                    throw new \Exception('Qualification Title not found');
                }

                $allocation = Allocation::where('legislator_id', $receiverLegislatorId)
                ->where('attributor_id', $senderLegislatorId)
                ->where('particular_id', $receiverParticularId)
                ->where('attributor_particular_id', $senderParticularId)
                ->where('scholarship_program_id', $data['scholarship_program_id'])
                ->where('year', $data['allocation_year'])
                ->whereNull('deleted_at')
                ->first();

                $institution = Tvi::find($data['tvi_id']);
                if (!$institution) {
                    throw new \Exception('Institution not found');
                }

                $skillPriority = $this->getSkillPriority(
                    $qualificationTitle->training_program_id,
                    $institution->district->province_id,
                    $data['allocation_year']
                );

                if (!$skillPriority) {
                    throw new \Exception('Skill Priority not found');
                }

                // $provinceAbdd = ProvinceAbdd::where('province_id', $institution->district->province_id)
                //     ->where('abdd_id', $data['abdd_id'] ?? null)
                //     ->where('year', $data['allocation_year'] ?? null)
                //     ->first();

                // if (!$provinceAbdd) {
                //     throw new \Exception('Province ABDD not found');
                // }

                $numberOfSlots = $data['number_of_slots'] ?? 0;
                $totalAmount = $qualificationTitle->pcc * $numberOfSlots;

                if ($allocation->balance < $totalAmount) {
                    throw new \Exception('Insufficient balance to process the transfer.');
                }
                $allocation->balance -= $totalAmount;
                $allocation->save();  

                // $provinceAbdd->available_slots -= $numberOfSlots;
                // $provinceAbdd->save();

                $skillPriority->available_slots -= $numberOfSlots;
                $skillPriority->save();

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
                    'number_of_slots' => $data['number_of_slots'] ?? 0,
                    'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
                    'total_cost_of_toolkit_pcc' => $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots,
                    'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
                    'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
                    'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
                    'total_new_normal_assisstance' => $qualificationTitle->new_normal_assistance * $numberOfSlots,
                    'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
                    'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
                    'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
                    'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
                    'total_amount' => $qualificationTitle->pcc * $numberOfSlots,
                    'appropriation_type' => $data['appropriation_type'],
                    'target_status_id' => 1,
                ]);

                TargetHistory::create([
                    'target_id' => $record->id,
                    'allocation_id' => $record->allocation->id,
                    'district_id' => $institution->district_id,
                    'municipality_id' => $institution->municipality_id,
                    'tvi_id' => $institution->id,
                    'tvi_name' => $institution->name,
                    'qualification_title_id' => $qualificationTitle->id,
                    'qualification_title_code' => $qualificationTitle->trainingProgram->code,
                    'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                    'abdd_id' => $data['abdd_id'],
                    'delivery_mode_id' => $data['delivery_mode_id'] ?? null,
                    'learning_mode_id' => $data['learning_mode_id'] ?? null,
                    'number_of_slots' => $data['number_of_slots'] ?? 0,
                    'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
                    'total_cost_of_toolkit_pcc' => $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots,
                    'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
                    'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
                    'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
                    'total_new_normal_assisstance' => $qualificationTitle->new_normal_assistance * $numberOfSlots,
                    'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
                    'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
                    'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
                    'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
                    'total_amount' => $qualificationTitle->pcc * $numberOfSlots,
                    'appropriation_type' => $data['appropriation_type'],
                    'description' => 'Target Modified',
                    'user_id' => Auth::user()->id,
                ]);

                return $record;
            });
        } catch (\Exception $e) {
            Log::error('Error updating record', ['exception' => $e]);
            throw $e;
        }
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
            throw new \Exception('Skill Priority not found.');
        }

        if ($skillPriority->available_slots <= 0) {
            $this->sendErrorNotification('No available slots in Skill Priority');
            throw new \Exception('No available slots in Skill Priority.');
        }

        return $skillPriority;
    }
}
