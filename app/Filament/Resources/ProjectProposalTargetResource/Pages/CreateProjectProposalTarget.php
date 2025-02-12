<?php

namespace App\Filament\Resources\ProjectProposalTargetResource\Pages;

use App\Filament\Resources\ProjectProposalTargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\SkillPrograms;
use App\Models\Status;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\Tvi;
use Exception;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateProjectProposalTarget extends CreateRecord
{
    protected static string $resource = ProjectProposalTargetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.project-proposal-targets.index') => 'Project Proposal',
            'Create',
        ];
    }

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
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected ?string $heading = 'Create Project Proposal';

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

                // $provinceAbdd = $this->getProvinceAbdd(
                //     $targetData['abdd_id'],
                //     $institution->district->province_id,
                //     $targetData['allocation_year']
                // );

                $skillPriority = $this->getSkillPriority(
                    $qualificationTitle->training_program_id,
                    $institution->district_id,
                    $institution->district->province_id,
                    $targetData['allocation_year']
                );
                $numberOfSlots = $targetData['number_of_slots'] ?? 0;
                $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots, $targetData['allocation_year'], $targetData['per_capita_cost']);

                if ($allocation->balance < round($totals['total_amount'], 2)) {
                    $this->sendErrorNotification('Insufficient allocation balance.');
                    throw new Exception('Insufficient allocation balance.');
                }

                // if ($provinceAbdd->available_slots < $numberOfSlots) {
                //     $this->sendErrorNotification('Insufficient slots available in Province Abdd.');
                //     throw new Exception('Insufficient slots available in Province Abdd.');
                // }

                if ($skillPriority->available_slots < $numberOfSlots) {
                    $this->sendErrorNotification('Insufficient slots available in Province Abdd.');
                    throw new Exception('Insufficient slots available in Province Abdd.');
                }

                // Create Target and Decrement Allocations/Slots
                $target = $this->createTarget($targetData, $allocation, $institution, $qualificationTitle, $totals);
                $allocation->decrement('balance', $totals['total_amount']);
                $skillPriority->decrement('available_slots', $numberOfSlots);

                // $provinceAbdd->decrement('available_slots', $numberOfSlots);

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
            'appropriation_type', 'abdd_id', 'learning_mode_id', 'delivery_mode_id'
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
            'soft_or_commitment' => 'Soft',
            'year' => $targetData['allocation_year']
        ])
        ->whereNull('attributor_id')
        ->first();

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

    // private function getProvinceAbdd(int $abddId, int $provinceId, int $appropriationYear): ProvinceAbdd
    // {
    //     $provinceAbdd = ProvinceAbdd::where([
    //         'abdd_id' => $abddId,
    //         'province_id' => $provinceId,
    //         'year' => $appropriationYear,
    //     ])->first();

    //     if (!$provinceAbdd) {
    //         $this->sendErrorNotification('Province Abdd Slots not found.');
    //         throw new Exception('Province Abdd Slots not found.');
    //     }

    //     if ($provinceAbdd->available_slots <= 0) {
    //         $this->sendErrorNotification('No available slots in Province Abdd.');
    //         throw new Exception('No available slots in Province Abdd.');
    //     }

    //     return $provinceAbdd;
    // }

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
                        ->where('year', $appropriationYear);
                })
                ->first();
        }
        
        $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

        return $skillsPriority;
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

    private function calculateTotals($qualificationTitle, $numberOfSlots, $year, $perCapitaCost): array
    {
        $quali = QualificationTitle::find($qualificationTitle->id);
        $costOfToolkitPcc = $quali->toolkits()->where('year', $year)->first();


        if (!$quali) {
            $this->sendErrorNotification('Qualification Title not found.');
            throw new Exception('Qualification Title not found.');
        }

        $step = ScholarshipProgram::where('name', 'STEP')->first();

        $totalCostOfToolkit = 0;
        $totalAmount = $perCapitaCost * $numberOfSlots;
        if ($quali->scholarship_program_id === $step->id) {
            
            if (!$costOfToolkitPcc) {
                $this->sendErrorNotification('Please add STEP Toolkits.');
                throw new Exception('Please add STEP Toolkits.');
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
            'total_new_normal_assisstance' => $qualificationTitle->new_normal_assisstance * $numberOfSlots,
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

    private function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Error')
            ->danger()
            ->body($message)
            ->send();
    }
}
