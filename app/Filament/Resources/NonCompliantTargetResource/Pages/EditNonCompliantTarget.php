<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\District;
use App\Models\Province;
use App\Models\ProvinceAbdd;
use App\Models\QualificationScholarship;
use App\Models\QualificationTitle;
use App\Models\SkillPriority;
use App\Models\SkillPrograms;
use App\Models\Status;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    protected function getRedirectUrl(): string
    {
        $record = $this->record;
        $attributionAllocation = $record->allocation->attributor;
        $program = $record->qualification_title->soc;

        if ($attributionAllocation) {
            if ($program === 1) {
                return route('filament.admin.resources.attribution-targets.index');
            }
            else {
                return route('filament.admin.resources.attribution-project-proposals.index');
            }
        } else {
            if ($program === 1) {
                return route('filament.admin.resources.targets.index');
            }
            else {
                return route('filament.admin.resources.project-proposal-targets.index');
            }
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

        $data['per_capita_cost'] = $record->total_amount / $record->number_of_slots;

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
                    $message = "Receiver Particular ID cannot be null.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }

                $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
                if (!$qualificationTitle) {
                    $message = "Qualification Title not found.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
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
                    $message = "Institution not found.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }

                $skillPriority = $this->getSkillPriority(
                    $qualificationTitle->training_program_id,
                    $institution->district_id,
                    $institution->district->province_id,
                    $data['allocation_year']
                );

                if (!$skillPriority) {
                    $message = "Skill Priority not found.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }

                $numberOfSlots = $data['number_of_slots'] ?? 0;
                
                if ($qualificationTitle->soc) {
                    $totalAmount = $qualificationTitle->pcc * $numberOfSlots;
                } else {
                    $totalAmount = $data['per_capita_cost'] * $numberOfSlots;
                }
                

                if ($allocation->balance < $totalAmount) {
                    $message = "Insufficient balance to process the transfer.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }
                $allocation->balance -= $totalAmount;
                $allocation->save();  

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
                    'total_amount' => $totalAmount,
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
            $message = "Failed to update target: " . $e->getMessage();
            NotificationHandler::handleValidationException('Something went wrong', $message);
            throw $e;
        }
    }
    private function getSkillPriority(int $trainingProgramId, $districtId, int $provinceId, int $appropriationYear)
    {
        $active = Status::where('desc', 'Active')->first();
        $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
            ->whereHas('skillPriority', function ($query) use ($districtId, $provinceId, $appropriationYear, $active) {
                $query->where('province_id', $provinceId)
                    ->where('district_id', $districtId)
                    ->where('year', $appropriationYear)
                    ->where('status_id', $active->id);
            })
            ->first();

        if (!$skillPrograms) {
            $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                ->whereHas('skillPriority', function ($query) use ($provinceId, $appropriationYear) {
                    $query->where('province_id', $provinceId)
                        ->whereNull('district_id')
                        ->where('year', $appropriationYear);
                })
                ->first();
        }
        
        $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

        if (!$skillsPriority) {
            $trainingProgram = TrainingProgram::where('id', $trainingProgramId)->first();
            $province = Province::where('id', $provinceId)->first();
            $district = District::where('id', $districtId)->first();
        
            if (!$trainingProgram || !$province || !$district) {
                NotificationHandler::handleValidationException('Something went wrong', 'Invalid training program, province, or district.');
                return;
            }
        
            $message = "Skill Priority for {$trainingProgram->title} under District {$district->id} in {$province->name} not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $skillsPriority;
    }
}
