<?php

namespace App\Filament\Resources\CompliantTargetsResource\Pages;

use App\Filament\Resources\CompliantTargetsResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateCompliantTargets extends CreateRecord
{
    protected static ?string $title = 'Mark as Compliant Target';

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.compliant-targets.index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.index') => 'Pending Targets',
            'Mark as Compliant'
        ];
    }
    protected static string $resource = CompliantTargetsResource::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    private const COMPLIANT_STATUS_DESC = 'Compliant';

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $targetRecord = $this->findTarget($data['target_id']);
            $allocation = $this->findAllocation($data);
            $compliantStatus = $this->findCompliantStatus();
            $qualificationTitle = $this->findQualificationTitle($data['qualification_title_id']);
            $numberOfSlots = $data['number_of_slots'] ?? 0;

            $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots);
            $existingTotalAmount = $targetRecord->total_amount ?? 0;
            $newTotalAmount = $totals['total_amount'];

            $this->resetAllocatiocationBalance($allocation, $existingTotalAmount);
            $this->updateAllocationBalance($allocation, $newTotalAmount);
            $this->createTargetHistory($targetRecord->id, $allocation->id, $data, $totals, $compliantStatus->id);
            $this->updateTargetRecord($targetRecord, $allocation->id, $data, $totals, $compliantStatus->id);

            return $targetRecord;
        });
    }

    private function findTarget($targetId): Target
    {
        return Target::findOrFail($targetId);
    }

    private function findAllocation(array $data): Allocation
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

    private function findQualificationTitle($qualificationTitleId): QualificationTitle
    {
        return QualificationTitle::findOrFail($qualificationTitleId);
    }

    private function calculateTotals(QualificationTitle $qualificationTitle, int $numberOfSlots): array
    {
        return [
            'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
            'total_cost_of_toolkit_pcc' => $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
            'total_new_normal_assistance' => $qualificationTitle->new_normal_assisstance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => $qualificationTitle->pcc * $numberOfSlots,
        ];
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
            'total_entrerpeneurship_fee' => $totals['total_entrepreneurship_fee'],
            'total_new_normal_assistance' => $totals['total_new_normal_assistance'],
            'total_accident_insurance' => $totals['total_accident_insurance'],
            'total_book_allowance' => $totals['total_book_allowance'],
            'total_uniform_allowance' => $totals['total_uniform_allowance'],
            'total_misc_fee' => $totals['total_misc_fee'],
            'total_amount' => $totals['total_amount'],
            'appropriation_type' => $data['appropriation_type'],
            'description' => 'Marked as Compliant'
        ]);
    }

    private function resetAllocatiocationBalance(Allocation $allocation, float $totalAmount): void {
        $allocation->balance += $totalAmount;
        $allocation->save();
    }

    private function updateAllocationBalance(Allocation $allocation, float $totalAmount): void
    {
        $allocation->balance -= $totalAmount;
        $allocation->save();
    }

    private function updateTargetRecord(Target $targetRecord, int $allocationId, array $data, array $totals, int $compliantStatusId): void
    {
        $targetRecord->update(array_merge($data, $totals, [
            'allocation_id' => $allocationId,
            'target_status_id' => $compliantStatusId,
        ]));
    }
}
