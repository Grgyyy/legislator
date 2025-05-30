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
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAttributionProjectProposal extends CreateRecord
{
    protected static string $resource = AttributionProjectProposalResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected static ?string $title = 'Create Attribution Project Proposal';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/attribution-targets' => 'Attribution Project Proposal',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): Target
    {
        try {
            return DB::transaction(function () use ($data) {

                if (empty($data['targets'])) {
                    $message = "No target data found.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                    throw new \Exception($message);
                }

                $lastCreatedTarget = null;

                foreach ($data['targets'] as $targetData) {
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
                        if (!array_key_exists($field, $targetData) || empty($targetData[$field])) {
                            $message = "The field '$field' is required.";
                            NotificationHandler::handleValidationException('Something went wrong', $message);
                            throw new \Exception($message);
                        }
                    }

                    $allocation = Allocation::where('attributor_id', $targetData['attribution_sender'])
                        ->where('legislator_id', $targetData['attribution_receiver'])
                        ->where('attributor_particular_id', $targetData['attribution_sender_particular'])
                        ->where('particular_id', $targetData['attribution_receiver_particular'])
                        ->where('scholarship_program_id', $targetData['attribution_scholarship_program'])
                        ->where('soft_or_commitment', 'Commitment')
                        ->where('year', $targetData['allocation_year'])
                        ->first();

                    if (!$allocation) {
                        $message = "Allocation not found.";
                        NotificationHandler::handleValidationException('Something went wrong', $message);
                        throw new \Exception($message);
                    }

                    // Retrieve the qualification title
                    $qualificationTitle = QualificationTitle::find($targetData['qualification_title_id']);
                    if (!$qualificationTitle) {
                        $message = "Qualification Title not found.";
                        NotificationHandler::handleValidationException('Something went wrong', $message);
                        throw new \Exception($message); 
                    }

                    $numberOfSlots = $targetData['number_of_slots'] ?? 0;

                    $step = ScholarshipProgram::where('name', 'STEP')->first();

                    $costOfToolkitPcc = $qualificationTitle->toolkits()->where('year', $targetData['allocation_year'])->first();
                    $totalCostOfToolkit = 0;
                    $totalAmount = $targetData['per_capita_cost'] * $numberOfSlots;

                    if ($qualificationTitle->scholarship_program_id === $step->id) {
                        if (!$costOfToolkitPcc) {
                            $message = "STEP Toolkits are required before proceeding. Please add them first.";
                            NotificationHandler::handleValidationException('Something went wrong', $message);
                            throw new \Exception($message);
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

                    $institution = Tvi::find($targetData['tvi_id']);
                    if (!$institution) {
                        $message = "Institution not found.";
                        NotificationHandler::handleValidationException('Something went wrong', $message);
                        throw new \Exception($message);
                    }

                    if ($allocation->balance < $total_amount) {
                        $message = "Insufficient funds in sender allocation.";
                        NotificationHandler::handleValidationException('Something went wrong', $message);
                        throw new \Exception($message); 
                    }

                    $skillPriority = $this->getSkillPriority(
                        $qualificationTitle->training_program_id,
                        $institution->district_id,
                        $institution->district->province_id,
                        $targetData['allocation_year']
                    );

                    if (!$skillPriority) {
                        $message = "Skill Priority not found.";
                        NotificationHandler::handleValidationException('Something went wrong', $message);
                        throw new \Exception($message);
                    }

                    if ($skillPriority->available_slots < $numberOfSlots) {
                        $message = "Not enough available slots in Skill Priority.";
                        NotificationHandler::handleValidationException('Something went wrong', $message);
                        throw new \Exception($message); 
                    }

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
                        'total_amount' => $total_amount,
                        'appropriation_type' => $targetData['attribution_appropriation_type'],
                        'target_status_id' => 1,
                    ]);

                    $allocation->balance -= $total_amount;
                    $allocation->save();

                    $skillPriority->decrement('available_slots', $numberOfSlots);

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
                        'total_amount' => $total_amount,
                        'appropriation_type' => $targetData['attribution_appropriation_type'],
                        'description' => 'Target Created',
                        'user_id' => Auth::user()->id,
                    ]);

                    $lastCreatedTarget = $target;
                }

                if (!$lastCreatedTarget) {
                    $message = "No targets were created.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                    throw new \Exception($message); 
                }

                $this->sendSuccessNotification('Project Proposal/s created successfully.');

                return $lastCreatedTarget;
            });
        } catch (\Exception $e) {
            NotificationHandler::handleValidationException('An error occurred', $e->getMessage());
            throw $e;
        }
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

    private function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Error')
            ->danger()
            ->body($message)
            ->send();
    }
}
