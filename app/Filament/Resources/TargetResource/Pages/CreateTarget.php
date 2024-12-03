<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Models\ProvinceAbdd;
use App\Models\Tvi;
use Exception;
use App\Models\Target;
use App\Models\Allocation;
use App\Models\TargetHistory;
use App\Models\QualificationTitle;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Filament\Resources\TargetResource;
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
            if (empty($data['targets'])) {
                $this->sendErrorNotification('No target data found.');
                throw new Exception('No target data found.');
            }

            $lastCreatedTarget = null;

            foreach ($data['targets'] as $targetData) {
                $this->validateTargetData($targetData);

                $allocation = $this->getAllocation($targetData);
                $institution = $this->getInstitution($targetData['tvi_id']);
                $qualificationTitle = $this->getQualificationTitle($targetData['qualification_title_id']);
                $provinceAbdd = $this->getProvinceAbdd(
                    $targetData['abdd_id'],
                    $institution->district->province_id,
                    $targetData['allocation_year']
                );

                $numberOfSlots = $targetData['number_of_slots'] ?? 0;
                $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots, $targetData);

                if ($allocation->balance < round($totals['total_amount'], 2)) {
                    $this->sendErrorNotification('Insufficient allocation balance.');
                    throw new Exception('Insufficient allocation balance.');
                }

                if ($provinceAbdd->available_slots < $numberOfSlots) {
                    $this->sendErrorNotification('Insufficient slots available in Province Abdd.');
                    throw new Exception('Insufficient slots available in Province Abdd.');
                }

                // Create Target and Decrement Allocations/Slots
                $target = $this->createTarget($targetData, $allocation, $institution, $qualificationTitle, $totals);
                $allocation->decrement('balance', $totals['total_amount']);
                $provinceAbdd->decrement('available_slots', $numberOfSlots);

                // Log the history
                $this->logTargetHistory($targetData, $target, $allocation, $totals);

                $lastCreatedTarget = $target;
            }

            if (!$lastCreatedTarget) {
                $this->sendErrorNotification('No targets were created.');
                throw new Exception('No targets were created.');
            }

            // Send success notification
            $this->sendSuccessNotification('Targets created successfully.');

            return $lastCreatedTarget;
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



    private function validateTargetData(array $targetData): void
    {
        $requiredFields = [
            'legislator_id', 'particular_id', 'scholarship_program_id',
            'qualification_title_id', 'number_of_slots', 'tvi_id',
            'appropriation_type', 'abdd_id', 'delivery_mode_id',
        ];

        foreach ($requiredFields as $field) {
            if (empty($targetData[$field])) {
                $this->sendErrorNotification("The field '$field' is required.");
                throw new \InvalidArgumentException("The field '$field' is required.");
            }
        }
    }

    private function getAllocation(array $targetData): Allocation
    {
        $allocation = Allocation::where([
            'legislator_id' => $targetData['legislator_id'],
            'particular_id' => $targetData['particular_id'],
            'scholarship_program_id' => $targetData['scholarship_program_id'],
            'year' => $targetData['allocation_year']
        ])->first();

        if (!$allocation) {
            $this->sendErrorNotification('Allocation not found.');
            throw new Exception('Allocation not found.');
        }

        return $allocation;
    }

    private function getInstitution(int $tviId): Tvi
    {
        $institution = Tvi::find($tviId);

        if (!$institution) {
            $this->sendErrorNotification('Institution not found.');
            throw new Exception('Institution not found.');
        }

        return $institution;
    }

    private function getProvinceAbdd(int $abddId, int $provinceId, int $appropriationYear): ProvinceAbdd
    {
        $provinceAbdd = ProvinceAbdd::where([
            'abdd_id' => $abddId,
            'province_id' => $provinceId,
            'year' => $appropriationYear,
        ])->first();

        if (!$provinceAbdd) {
            $this->sendErrorNotification('Province Abdd Slots not found.');
            throw new Exception('Province Abdd Slots not found.');
        }

        if ($provinceAbdd->available_slots <= 0) {
            $this->sendErrorNotification('No available slots in Province Abdd.');
            throw new Exception('No available slots in Province Abdd.');
        }

        return $provinceAbdd;
    }

    private function getQualificationTitle(int $qualificationTitleId): QualificationTitle
    {
        $qualificationTitle = QualificationTitle::find($qualificationTitleId);

        if (!$qualificationTitle) {
            $this->sendErrorNotification('Qualification Title not found.');
            throw new Exception('Qualification Title not found.');
        }

        return $qualificationTitle;
    }

    private function calculateTotals(QualificationTitle $qualificationTitle, int $numberOfSlots, array $targetData): array
    {
        return [
            'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
            'total_cost_of_toolkit_pcc' => $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
            'total_new_normal_assisstance' => $qualificationTitle->new_normal_assisstance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => ($qualificationTitle->pcc * $numberOfSlots) + $targetData['admin_cost'],
        ];
    }

    private function createTarget(array $targetData, Allocation $allocation, Tvi $institution, QualificationTitle $qualificationTitle, array $totals): Target
    {
        return Target::create(array_merge($targetData, [
            'allocation_id' => $allocation->id,
            'district_id' => $institution->district_id,
            'municipality_id' => $institution->municipality_id,
            'qualification_title_id' => $qualificationTitle->id,
            'tvi_name' => $institution->name,
            'qualification_title_code' => $qualificationTitle->trainingProgram->code,
            'qualification_title_name' => $qualificationTitle->trainingProgram->title,
            'number_of_slots' => $targetData['number_of_slots'],
            'target_status_id' => 1,
        ], $totals));
    }

    private function logTargetHistory(array $targetData, Target $target, Allocation $allocation, array $totals): void
    {
        TargetHistory::create([
            'target_id' => $target->id,
            'allocation_id' => $allocation->id,
            'district_id' => $target->district_id,
            'municipality_id' => $target->municipality_id,
            'tvi_id' => $targetData['tvi_id'],
            'tvi_name' => $target->tvi_name,
            'qualification_title_id' => $target->qualification_title_id,
            'qualification_title_code' => $target->qualification_title_code,
            'qualification_title_name' => $target->qualification_title_name,
            'abdd_id' => $targetData['abdd_id'],
            'delivery_mode_id' => $targetData['delivery_mode_id'],
            'number_of_slots' => $targetData['number_of_slots'],
            'attribution_allocation_id' => $targetData['attribution_allocation_id'] ?? null,
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
            'admin_cost' => $targetData['admin_cost'],
            'total_amount' => $totals['total_amount'],
            'appropriation_type' => $targetData['appropriation_type'],
            'description' => 'Target Created',
        ]);
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
