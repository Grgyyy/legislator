<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditTarget extends EditRecord
{
    protected static string $resource = TargetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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

    protected ?string $heading = 'Edit Target';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.edit', ['record' => $this->record->id]) => 'Target',
            'Edit'
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;

        $data['legislator_id'] = $data['legislator_id'] ?? $allocation->legislator_id ?? null;
        $data['particular_id'] = $data['particularId'] ?? $allocation->particular_id ?? null;
        $data['scholarship_program_id'] = $data['scholarship_program_id'] ?? $allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $data['allocation_year'] ?? $allocation->year ?? null;
        $data['abscap_id'] = $data['abscap_id'] ?? $record->abscap_id ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $this->validateTargetData($data);

            $allocation = $this->getAllocation($data);
            $institution = $this->getInstitution($data['tvi_id']);
            $qualificationTitle = $this->getQualificationTitle($data['qualification_title_id']);
            $allocationYear = $allocation->year;

            $skillPriority = $this->getSkillPriority(
                $qualificationTitle->trainingProgram->id,
                $institution->district,
                $institution->district->province_id,
                $data['allocation_year']
            );

            $numberOfSlots = $data['number_of_slots'] ?? 0;
            $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots, $data);

            $previousSlots = $record->number_of_slots;
            $previousSkillPrio = $this->getSkillPriority(
                $record->qualification_title->training_program_id,
                $record->tvi->district,
                $institution->district->province_id,
                $data['allocation_year']
            );

            if (!$previousSkillPrio) {
                $message = "Previous Skill Priority not found.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            $allocation->increment('balance', $record->total_amount);
            $previousSkillPrio->increment('available_slots', $previousSlots);

            if ($allocation->balance < round($totals['total_amount'], 2)) {
                $message = "Insufficient allocation balance.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            if ($skillPriority->available_slots < $numberOfSlots) {
                $message = "Insufficient slots available in Skill Priority.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            $record->update(array_merge($totals, [
                'abscap_id' => $data['abscap_id'] ?? null,
                'allocation_id' => $allocation->id,
                'district_id' => $institution->district_id,
                'municipality_id' => $institution->municipality_id,
                'qualification_title_id' => $qualificationTitle->id,
                'tvi_name' => $institution->name,
                'qualification_title_code' => $qualificationTitle->trainingProgram->code,
                'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code,
                'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                'number_of_slots' => $data['number_of_slots'],
                'learning_mode_id' => $data['learning_mode_id'] ?? null,
                'delivery_mode_id' => $data['delivery_mode_id'],
                'target_status_id' => 1,
            ]));

            $allocation->decrement('balance', $totals['total_amount']);
            $skillPriority->decrement('available_slots', $numberOfSlots);

            $this->logTargetHistory($data, $record, $allocation, $totals);


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

    private function validateTargetData(array $data): void
    {
        $requiredFields = [
            'legislator_id',
            'particular_id',
            'scholarship_program_id',
            'qualification_title_id',
            'number_of_slots',
            'tvi_id',
            'appropriation_type',
            'abdd_id'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $message = "The field '$field' is required.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }
        }
    }

    private function getAllocation(array $data): Allocation
    {
        $allocation = Allocation::where([
            'legislator_id' => $data['legislator_id'],
            'particular_id' => $data['particular_id'],
            'scholarship_program_id' => $data['scholarship_program_id'],
            'soft_or_commitment' => 'Soft',
            'year' => $data['allocation_year']
        ])->first();

        if (!$allocation) {
            $message = "Allocation not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $allocation;
    }

    private function getInstitution(int $tviId): Tvi
    {
        $institution = Tvi::find($tviId);

        if (!$institution) {
            $message = "Institution not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $institution;
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

    private function getQualificationTitle(int $qualificationTitleId): QualificationTitle
    {
        $qualificationTitle = QualificationTitle::find($qualificationTitleId);

        if (!$qualificationTitle) {
            $message = "Qualification Title not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $qualificationTitle;
    }

    private function calculateTotals(QualificationTitle $qualificationTitle, int $numberOfSlots, array $data): array
    {
        $quali = QualificationTitle::find($qualificationTitle->id);
        $costOfToolkitPcc = $quali->toolkits()->where('year', $data['allocation_year'])->first();

        if (!$quali) {
            $message = "Qualification Title not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $step = ScholarshipProgram::where('name', 'STEP')->first();

        $totalCostOfToolkit = 0;
        $totalAmount = $qualificationTitle->pcc * $numberOfSlots;
        if ($quali->scholarship_program_id === $step->id) {
            if (!$costOfToolkitPcc) {
                $message = "STEP Toolkits are required before proceeding. Please add them first";
                NotificationHandler::handleValidationException('Something went wrong', $message);
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

    private function logTargetHistory(array $targetData, Target $target, Allocation $allocation, array $totals): void
    {
        TargetHistory::create([
            'abscap_id' => $targetData['abscap_id'] ?? null,
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
            'learning_mode_id' => $targetData['learning_mode_id'] ?? null,
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
            'description' => 'Target Modified',
            'user_id' => Auth::user()->id,
        ]);
    }
}
