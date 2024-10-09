<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\NonCompliantRemark;
use App\Models\QualificationTitle;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Log;

class CreateNonCompliantTarget extends CreateRecord
{
    protected static ?string $title = 'Mark as Non-Compliant Target';

    protected static string $resource = NonCompliantTargetResource::class;

    private const COMPLIANT_STATUS_DESC = 'Non-Compliant';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.index') => 'Pending Target',
            'Mark as Non-Compliant'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.non-compliant-targets.index');
    }


    protected function handleRecordCreation(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data) {
                $targetRecord = Target::find($data['target_id']);
                
                if (!$targetRecord) {
                    throw new \Exception('Target not found.');
                }
                
                $nonCompliantRecord = TargetStatus::where('desc', self::COMPLIANT_STATUS_DESC)->first();
                
                if (!$nonCompliantRecord) {
                    throw new \Exception('Compliant status not found.');
                }

                if ($targetRecord->targetStatus->desc !== 'Non-Compliant') {
                    $this->createTargetHistory($targetRecord);
                    $this->updateTargetRecord($targetRecord, $nonCompliantRecord->id);
                    $this->updateAllocation($targetRecord);
                } else {
                    throw new \Exception('Target already marked as Non-Compliant.');
                }
                
                if (isset($data['remarks_id'])) {
                    $this->createTargetRemark($targetRecord, $data);
                }

                return $targetRecord;
            });
        } catch (\Exception $e) {
            throw new \Exception("Failed to update target: no changes were saved.");
        }
    }



    private function createTargetHistory($targetRecord) {
        TargetHistory::create([
            'target_id' => $targetRecord->id,
            'allocation_id' => $targetRecord->allocation_id,
            'tvi_id' => $targetRecord->tvi_id,
            'qualification_title_id' => $targetRecord->qualification_title_id,
            'abdd_id' => $targetRecord->abdd_id,
            'number_of_slots' => $targetRecord->number_of_slots,
            'total_training_cost_pcc' => $targetRecord->total_training_cost_pcc,
            'total_cost_of_toolkit_pcc' => $targetRecord->total_cost_of_toolkit_pcc,
            'total_training_support_fund' => $targetRecord->total_training_support_fund,
            'total_assessment_fee' => $targetRecord->total_assessment_fee,
            'total_entrepeneurship_fee' => $targetRecord->total_entrepeneurship_fee,
            'total_new_normal_assistance' => $targetRecord->total_new_normal_assistance,
            'total_accident_insurance' => $targetRecord->total_accident_insurance,
            'total_book_allowance' => $targetRecord->total_book_allowance ?? 0,
            'total_uniform_allowance' => $targetRecord->total_uniform_allowance,
            'total_misc_fee' => $targetRecord->total_misc_fee,
            'total_amount' => $targetRecord->total_amount,
            'appropriation_type' => $targetRecord->appropriation_type,
            'description' => 'Marked as Non-Compliant'
        ]);
    }

    private function updateTargetRecord($targetRecord, $nonCompliantId) {
        $targetRecord->update([
            'target_status_id' => $nonCompliantId,
        ]);
    }
    

    private function createTargetRemark($targetRecord, $data) {
        NonCompliantRemark::create([
            'target_id' => $targetRecord->id,
            'target_remarks_id' => $data['remarks_id'],
            'others_remarks' => $data['other_remarks'],
        ]);
    }

    private function updateAllocation($targetRecord) {
        $allocation = Allocation::find($targetRecord->allocation_id);
        $total_costing = $targetRecord->total_amount;
    
        if ($allocation) {
            $allocation->balance += $total_costing;
            $allocation->save();
        }
    }

}
