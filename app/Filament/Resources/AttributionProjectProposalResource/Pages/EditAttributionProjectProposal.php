<?php

namespace App\Filament\Resources\AttributionProjectProposalResource\Pages;

use App\Filament\Resources\AttributionProjectProposalResource;
use App\Models\Allocation;
use App\Models\District;
use App\Models\Province;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\SkillPrograms;
use App\Models\Status;
use App\Models\TargetHistory;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Auth;
use Exception;
use Filament\Notifications\Notification;
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
                'attribution_sender',
                'attribution_sender_particular',
                'attribution_scholarship_program',
                'allocation_year',
                'attribution_appropriation_type',
                'attribution_receiver',
                'attribution_receiver_particular',
                'tvi_id',
                'qualification_title_id',
                'abdd_id',
                'number_of_slots',
            ];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $message = "The field '$field' is required.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }
            }

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
                $message = "Qualification Title not found.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            $numberOfSlots = $data['number_of_slots'] ?? 0;

            $step = ScholarshipProgram::where('name', 'STEP')->first();

            $costOfToolkitPcc = $qualificationTitle->toolkits()->where('year', $data['allocation_year'])->first();
            $totalCostOfToolkit = 0;
            $totalAmount = $data['per_capita_cost'] * $numberOfSlots;

            if ($qualificationTitle->scholarship_program_id === $step->id) {

                if (!$costOfToolkitPcc) {
                    $message = "STEP Toolkits are required before proceeding. Please add them first";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
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

            $total_amount = $totalAmount;
            $institution = Tvi::find($data['tvi_id']);
            if (!$institution) {
                $message = "Institution not found.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            if ($allocation->balance + $record->total_amount < $total_amount) {
                throw new Exception('Insufficient funds in sender allocation');
            }

            $previousSkillPrio = $this->getSkillPriority(
                $record->qualification_title->training_program_id,
                $record->tvi->district_id,
                $record->tvi->district->province_id,
                $record->allocation->year
            );

            if (!$previousSkillPrio) {
                $trainingProgram = TrainingProgram::where('id', $record->qualification_title->training_program_id)->first();
                $province = Province::where('id', $record->tvi->district->province_id)->first();
                $district = District::where('id', $record->tvi->district_id)->first();

                if (!$trainingProgram || !$province || !$district) {
                    NotificationHandler::handleValidationException('Something went wrong', 'Invalid training program, province, or district.');
                    return;
                }

                $message = "Skill Priority for {$trainingProgram->title} under District {$district->id} in {$province->name} not found.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            $skillPriority = $this->getSkillPriority(
                $qualificationTitle->training_program_id,
                $institution->district_id,
                $institution->district->province_id,
                $data['allocation_year']
            );

            if (!$skillPriority) {
                $trainingProgram = TrainingProgram::where('id', $qualificationTitle->training_program_id)->first();
                $province = Province::where('id', $institution->district->province_id)->first();
                $district = District::where('id', $institution->district_id)->first();

                if (!$trainingProgram || !$province || !$district) {
                    NotificationHandler::handleValidationException('Something went wrong', 'Invalid training program, province, or district.');
                    return;
                }

                $message = "Skill Priority for {$trainingProgram->title} under District {$district->id} in {$province->name} not found.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
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

            $this->sendSuccessNotification('Project Proposal/s created successfully.');

            return $record;
        });
    }

    private function sendSuccessNotification(string $message): void
    {
        Notification::make()
            ->title('Success')
            ->success()
            ->body($message)
            ->send();
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
