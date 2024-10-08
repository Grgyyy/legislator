<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetRemark;
use App\Models\TargetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateNonCompliantTarget extends CreateRecord
{
    protected static string $resource = NonCompliantTargetResource::class;

    private const COMPLIANT_STATUS_DESC = 'Non-Compliant';

    public function getBreadcrumbs(): array
    {
        return [
            'Non-Compliant Targets',
            'List',
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            try {
                $targetRecord = $this->findTarget($data['target_id']);
                $remarkRecord = $this->findRemark($data['target_remarks_id']);
                $allocation = $this->findAllocation($targetRecord->allocation);
                $compliantStatus = $this->findCompliantStatus();
                $qualificationTitle = $this->findQualificationTitle($data['qualification_title_id']);
                $numberOfSlots = $data['number_of_slots'] ?? 0;

                $othersRemark = $data['others_remarks'];

                $totals = $this->calculateTotalsForQualificationTitle($qualificationTitle, $numberOfSlots);

                $this->createTargetHistory($targetRecord->id, $allocation->id, $data, $totals, $compliantStatus->id);
                $this->updateTargetRecord($targetRecord, $allocation->id, $data, $totals, $compliantStatus->id);
                $this->createTargetRemark($targetRecord->id, $remarkRecord->id, $othersRemark);

                return $targetRecord;
            } catch (\Exception $e) {
                // Handle exception (e.g., log error, return a response, etc.)
                // You may want to throw a custom exception or return an error message
                throw $e;
            }
        });
    }

    private function findTarget(int $targetId): Target
    {
        return Target::findOrFail($targetId);
    }

    private function findRemark(int $remarkId): TargetRemark
    {
        return TargetRemark::findOrFail($remarkId);
    }

    private function findAllocation(array $data)
    {
        return Allocation::where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['allocation_year'])
            ->firstOrFail();
    }

    private function findCompliantStatus(): TargetStatus
    {
        return TargetStatus::where('desc', self::COMPLIANT_STATUS_DESC)->firstOrFail();
    }

    private function findQualificationTitle(int $qualificationTitleId): QualificationTitle
    {
        return QualificationTitle::findOrFail($qualificationTitleId);
    }

    private function calculateTotalsForQualificationTitle(QualificationTitle $qualificationTitle, int $numberOfSlots): array
    {
        return [
            'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
            'total_cost_of_toolkit_pcc' => $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepeneurship_fee' => $qualificationTitle->entrepeneurship_fee * $numberOfSlots,
            'total_new_normal_assistance' => $qualificationTitle->new_normal_assisstance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => $qualificationTitle->pcc * $numberOfSlots,
        ];
    }

    private function updateTargetRecord(Target $targetRecord, int $allocationId, array $data, array $totals, int $compliantStatusId): void
    {
        $targetRecord->update(array_merge($data, $totals, [
            'allocation_id' => $allocationId,
            'target_status_id' => $compliantStatusId,
        ]));
    }

    private function createTargetHistory(int $targetId, int $allocationId, array $data, array $totals, int $compliantStatusId): void
    {
        TargetHistory::create([
            'target_id' => $targetId,
            'allocation_id' => $allocationId,
            'tvi_id' => $data['tvi_id'],
            'qualification_title_id' => $data['qualification_title_id'],
            'abdd_id' => $data['abdd_id'],
            'number_of_slots' => $data['number_of_slots'],
            'total_training_cost_pcc' => $totals['total_training_cost_pcc'],
            'total_cost_of_toolkit_pcc' => $totals['total_cost_of_toolkit_pcc'],
            'total_training_support_fund' => $totals['total_training_support_fund'],
            'total_assessment_fee' => $totals['total_assessment_fee'],
            'total_entrepeneurship_fee' => $totals['total_entrepeneurship_fee'],
            'total_new_normal_assistance' => $totals['total_new_normal_assistance'],
            'total_accident_insurance' => $totals['total_accident_insurance'],
            'total_book_allowance' => $totals['total_book_allowance'],
            'total_uniform_allowance' => $totals['total_uniform_allowance'],
            'total_misc_fee' => $totals['total_misc_fee'],
            'total_amount' => $totals['total_amount'],
            'appropriation_type' => $data['appropriation_type'],
            'description' => 'Marked as Compliant',
        ]);
    }

    private function createTargetRemark(int $targetId, int $remarkId, string $othersRemark): void
    {
        TargetRemark::create([
           'target_id' => $targetId,
           'target_remarks_id' => $remarkId,
           'other_remarks' => $othersRemark
        ]);
    }
}
