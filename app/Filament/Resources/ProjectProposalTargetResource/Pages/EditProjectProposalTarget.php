<?php

namespace App\Filament\Resources\ProjectProposalTargetResource\Pages;

use App\Filament\Resources\ProjectProposalTargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\Tvi;
use Exception;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class EditProjectProposalTarget extends EditRecord
{
    protected static string $resource = ProjectProposalTargetResource::class;

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

    protected ?string $heading = 'Edit Project Proposal';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.project-proposal-targets.index') => 'Project Proposal',
            'Edit'
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;

        // Set default data values if not already set
        $data['legislator_id'] = $data['legislator_id'] ?? $allocation->legislator_id ?? null;
        $data['particular_id'] = $data['particularId'] ?? $allocation->particular_id ?? null;
        $data['scholarship_program_id'] = $data['scholarship_program_id'] ?? $allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $data['allocation_year'] ?? $allocation->year ?? null;
        $data['abscap_id'] = $data['abscap_id'] ?? $record->abscap_id ?? null;
        $data['per_capita_cost'] = $data['per_capita_cost'] ?? $record->total_amount / $record->number_of_slots ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $this->validateTargetData($data);

            $allocation = $this->getAllocation($data);
            $institution = $this->getInstitution($data['tvi_id']);
            $qualificationTitle = $this->getQualificationTitle($data['qualification_title_id']);

            $skillPriority = $this->getSkillPriority(
                $qualificationTitle->training_program_id,
                $institution->district->province_id,
                $data['allocation_year']
            );

            $numberOfSlots = $data['number_of_slots'] ?? 0;
            $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots, $data['allocation_year'], $data['per_capita_cost']);

            $previousSlots = $record->number_of_slots;
            $previousSkillPrio = SkillPriority::where([
                'training_program_id' => $record->qualification_title->training_program_id,
                'province_id' => $record->tvi->district->province_id,
                'year' => $record->allocation->year,
            ])->first();

            if (!$previousSkillPrio) {
                $this->sendErrorNotification('Previous Skill Priority not found.');
                throw new Exception('Previous Skill Priority not found.');
            }

            $allocation->increment('balance', $record->total_amount);
            $previousSkillPrio->increment('available_slots', $previousSlots);

            if ($allocation->balance <= $totals['total_amount']) {
                $this->sendErrorNotification('Insufficient allocation balance.');
                throw new Exception('Insufficient allocation balance.');
            }

            if ($skillPriority->available_slots < $numberOfSlots) {
                $this->sendErrorNotification('Insufficient slots available in Skill Priority.');
                throw new Exception('Insufficient slots available in Skill Priority.');
            }

            $record->update(array_merge($totals, [
                'allocation_id' => $allocation->id,
                'district_id' => $institution->district_id,
                'municipality_id' => $institution->municipality_id,
                'qualification_title_id' => $qualificationTitle->id,
                'tvi_name' => $institution->name,
                'qualification_title_code' => $qualificationTitle->trainingProgram->code,
                'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code,
                'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                'number_of_slots' => $data['number_of_slots'],
                'learning_mode_id' => $data['learning_mode_id'],
                'delivery_mode_id' => $data['delivery_mode_id'],
                'target_status_id' => 1,
            ]));

            $allocation->decrement('balance', $totals['total_amount']);
            $skillPriority->decrement('available_slots', $numberOfSlots);

            $this->logTargetHistory($data, $record, $allocation, $totals);

            $this->sendSuccessNotification('Target updated successfully.');

            // dd($data, $qualificationTitle->trainingProgram->title);

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
            'legislator_id', 'particular_id', 'scholarship_program_id',
            'qualification_title_id', 'number_of_slots', 'tvi_id',
            'appropriation_type', 'abdd_id', 'learning_mode_id',
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->sendErrorNotification("The field '$field' is required.");
                throw new \InvalidArgumentException("The field '$field' is required.");
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
            $this->sendErrorNotification('No available slots in Skill Priority.');
            throw new Exception('No available slots in Skill Priority.');
        }

        return $skillPriority;
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
            'description' => 'Target Modified',
            'user_id' => Auth::user()->id,
        ]);
    }
}
