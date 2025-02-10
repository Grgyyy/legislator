<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateTarget extends CreateRecord
{
    protected static string $resource = TargetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected static ?string $title = 'Create Pending Targets';

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/pending-targets' => 'Pending Targets',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): Target
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['targets'])) {
                NotificationHandler::sendErrorNotification('Something went wrong', 'No targets found');

                return;
            }

            $lastCreatedTarget = null;

            foreach ($data['targets'] as $targetData) {
                $allocation = $this->getAllocation($targetData);
                $institution = $this->getInstitution($targetData['tvi_id']);
                $qualificationTitle = $this->getQualificationTitle($targetData['qualification_title_id']);

                $skillPriority = $this->getSkillPriority(
                    $qualificationTitle->training_program_id,
                    $institution->district->province_id,
                    $targetData['allocation_year']
                );
                $numberOfSlots = $targetData['number_of_slots'] ?? 0;
                $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots, $targetData['allocation_year']);

                if ($allocation->balance < round($totals['total_amount'], 2)) {
                    NotificationHandler::sendErrorNotification('Something went wrong', 'Insufficient allocation balance.');

                    return;
                }

                if ($skillPriority->available_slots < $numberOfSlots) {
                    NotificationHandler::sendErrorNotification('Something went wrong', 'Insufficient slots available in skill priorities.');

                    return;
                }

                $target = $this->createTarget($targetData, $allocation, $institution, $qualificationTitle, $totals);
                $allocation->decrement('balance', $totals['total_amount']);
                $skillPriority->decrement('available_slots', $numberOfSlots);

                $this->logTargetHistory($targetData, $target, $allocation, $totals);

                $lastCreatedTarget = $target;
            }

            if (!$lastCreatedTarget) {
                NotificationHandler::sendErrorNotification('Something went wrong', 'No targets are created.');

                return;
            }

            NotificationHandler::sendSuccessNotification('Created', 'Target has been created successfully.');

            return $lastCreatedTarget;
        });
    }

    private function getAllocation(array $targetData): Allocation
    {
        $allocation = Allocation::where([
            'legislator_id' => $targetData['legislator_id'],
            'particular_id' => $targetData['particular_id'],
            'scholarship_program_id' => $targetData['scholarship_program_id'],
            'soft_or_commitment' => 'Soft',
            'year' => $targetData['allocation_year']
        ])->first();

        if (!$allocation) {
            NotificationHandler::sendErrorNotification('Something went wrong', 'Allocation not found');
        }

        return $allocation;
    }

    private function getInstitution(int $tviId): Tvi
    {
        $institution = Tvi::find($tviId);

        if (!$institution) {
            NotificationHandler::sendErrorNotification('Something went wrong', 'Institution not found');
        }

        return $institution;
    }

    private function getSkillPriority(int $trainingProgram, int $provinceId, int $appropriationYear): SkillPriority
    {
        $skillPriority = SkillPriority::where([
            'training_program_id' => $trainingProgram,
            'province_id' => $provinceId,
            'year' => $appropriationYear,
        ])->first();

        if (!$skillPriority) {
            NotificationHandler::sendErrorNotification('Something went wrong', 'Skill priority not found');
        }

        if ($skillPriority->available_slots <= 0) {
            NotificationHandler::sendErrorNotification('Something went wrong', 'Insufficient slots available in skill priorities.');
        }

        return $skillPriority;
    }

    private function getQualificationTitle(int $qualificationTitleId): QualificationTitle
    {
        $qualificationTitle = QualificationTitle::find($qualificationTitleId);

        if (!$qualificationTitle) {
            NotificationHandler::sendErrorNotification('Something went wrong', 'Qualification title not found');
        }

        return $qualificationTitle;
    }

    private function calculateTotals(QualificationTitle $qualificationTitle, int $numberOfSlots, int $year): array
    {
        $quali = QualificationTitle::find($qualificationTitle->id);
        $costOfToolkitPcc = $quali->toolkits()->where('year', $year)->first();


        if (!$quali) {
            NotificationHandler::sendErrorNotification('Something went wrong', 'Qualification title not found');
        }

        $step = ScholarshipProgram::where('name', 'STEP')->first();

        $totalCostOfToolkit = 0;
        $totalAmount = $qualificationTitle->pcc ? $qualificationTitle->pcc * $numberOfSlots : 0;
        
        if ($quali->scholarship_program_id === $step->id) {
            if (!$costOfToolkitPcc) {
                NotificationHandler::sendErrorNotification('Something went wrong', 'Please add STEP Toolkits');
            }

            $totalCostOfToolkit = $costOfToolkitPcc->price_per_toolkit * $numberOfSlots;
            $totalAmount += $totalCostOfToolkit;
        }

        return [
            'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
            'total_cost_of_toolkit_pcc' => $totalCostOfToolkit,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
            'total_new_normal_assisstance' => $qualificationTitle->new_normal_assistance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => $totalAmount,
        ];
    }

    private function createTarget(array $targetData, Allocation $allocation, Tvi $institution, QualificationTitle $qualificationTitle, array $totals): Target
    {
        return Target::create(array_merge($targetData, [
            // 'abscap_id' => $targetData['abscap_id'] ??  null,
            'allocation_id' => $allocation->id,
            'district_id' => $institution->district_id,
            'municipality_id' => $institution->municipality_id,
            'qualification_title_id' => $qualificationTitle->id,
            'tvi_name' => $institution->name,
            'qualification_title_code' => $qualificationTitle->trainingProgram->code,
            'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code,
            'qualification_title_name' => $qualificationTitle->trainingProgram->title,
            'number_of_slots' => $targetData['number_of_slots'],
            'learning_mode_id' => $targetData['learning_mode_id'],
            'delivery_mode_id' => $targetData['delivery_mode_id'],
            'target_status_id' => 1,
        ], $totals));
    }

    private function logTargetHistory(array $targetData, Target $target, Allocation $allocation, array $totals): void
    {
        TargetHistory::create([
            // 'abscap_id' => $target->abscap_id,
            'target_id' => $target->id,
            'allocation_id' => $allocation->id,
            'district_id' => $target->district_id,
            'municipality_id' => $target->municipality_id,
            'tvi_id' => $targetData['tvi_id'],
            'tvi_name' => $target->tvi_name,
            'qualification_title_id' => $target->qualification_title_id,
            'qualification_title_code' => $target->qualification_title_code,
            'qualification_title_soc_code' => $target->qualification_title_soc_code,
            'qualification_title_name' => $target->qualification_title_name,
            'abdd_id' => $targetData['abdd_id'],
            'delivery_mode_id' => $targetData['delivery_mode_id'],
            'learning_mode_id' => $targetData['learning_mode_id'],
            'number_of_slots' => $targetData['number_of_slots'],
            'total_training_cost_pcc' => $totals['total_training_cost_pcc'],
            'total_cost_of_toolkit_pcc' => $totals['total_cost_of_toolkit_pcc'],
            'total_training_support_fund' => $totals['total_training_support_fund'],
            'total_assessment_fee' => $totals['total_assessment_fee'],
            'total_entrepreneurship_fee' => $totals['total_entrepreneurship_fee'],
            'total_new_normal_assisstance' => $totals['total_new_normal_assisstance'],
            'total_accident_insurance' => $totals['total_accident_insurance'],
            'total_book_allowance' => $totals['total_book_allowance'],
            'total_uniform_allowance' => $totals['total_uniform_allowance'],
            'total_misc_fee' => $totals['total_misc_fee'],
            'total_amount' => $totals['total_amount'],
            'appropriation_type' => $targetData['appropriation_type'],
            'description' => 'Target Created',
            'user_id' => Auth::user()->id,
        ]);
    }
}