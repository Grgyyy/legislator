<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\QualificationScholarship;
use App\Models\QualificationTitle;
use App\Models\TargetHistory;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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
        return route('filament.admin.resources.targets.index');
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;

        $data['legislator_id'] = $data['legislator_id'] ?? $allocation->legislator_id ?? null;
        $data['particular_id'] = $data['particularId'] ?? $allocation->particular_id ?? null;
        $data['scholarship_program_id'] = $data['scholarship_program_id'] ?? $allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $data['allocation_year'] ?? $allocation->year ?? null;
        $data['target_id'] = $data['target_id'] ?? $record->id ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $allocation = $this->getAllocation($data);
            $qualificationTitle = $this->getQualificationTitle($data);

            $numberOfSlots = $data['number_of_slots'] ?? 0;
            $totalAmount = $this->calculateTotalAmount($qualificationTitle, $numberOfSlots);

            if ($allocation->balance >= $totalAmount) {
                $this->updateRecord($record, $allocation, $data, $qualificationTitle, $numberOfSlots, $totalAmount);
                $this->logTargetHistory($record, $allocation, $data, $qualificationTitle, $numberOfSlots, $totalAmount);

                return $record;
            } else {
                throw new \Exception('Insufficient balance for allocation');
            }
        });
    }


    private function getAllocation(array $data): Allocation
    {
        $allocation = Allocation::where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['allocation_year'])
            ->first();

        if (!$allocation) {
            throw new \Exception('Allocation not found for the provided legislator and scholarship program.');
        }

        return $allocation;
    }

    private function getQualificationTitle(array $data): QualificationTitle
    {
        $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
        if (!$qualificationTitle) {
            throw new \Exception('Qualification Title not found');
        }

        return $qualificationTitle;
    }

    private function calculateTotalAmount(QualificationTitle $qualificationTitle, int $numberOfSlots): float
    {
        return $qualificationTitle->pcc * $numberOfSlots;
    }


    private function updateRecord(Model $record, Allocation $allocation, array $data, QualificationTitle $qualificationTitle, int $numberOfSlots, float $totalAmount): void
    {
        $totalTrainingCostPcc = $qualificationTitle->training_cost_pcc * $numberOfSlots;

        $record->update([
            'allocation_id' => $allocation->id,
            'tvi_id' => $data['tvi_id'],
            'qualification_title_id' => $data['qualification_title_id'],
            'abdd_id' => $data['abdd_id'],
            'number_of_slots' => $data['number_of_slots'],
            'total_training_cost_pcc' => $totalTrainingCostPcc,
            'total_cost_of_toolkit_pcc' => $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
            'total_new_normal_assisstance' => $qualificationTitle->new_normal_assisstance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => $totalAmount,
            'appropriation_type' => $data['appropriation_type'],
            'target_status_id' => 1,
        ]);

        $allocation->balance -= $totalAmount;
        $allocation->save();
    }

    private function logTargetHistory(Model $record, Allocation $allocation, array $data, QualificationTitle $qualificationTitle, int $numberOfSlots, float $totalAmount): void
    {
        TargetHistory::create([
            'target_id' => $record->id,
            'allocation_id' => $allocation->id,
            'tvi_id' => $data['tvi_id'],
            'qualification_title_id' => $data['qualification_title_id'],
            'abdd_id' => $data['abdd_id'],
            'number_of_slots' => $data['number_of_slots'],
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
            'total_amount' => $totalAmount,
            'appropriation_type' => $data['appropriation_type'],
            'description' => 'Non-Compliant Reprocessed',
        ]);
    }

}
