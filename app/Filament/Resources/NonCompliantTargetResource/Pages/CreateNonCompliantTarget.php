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
                    $senderAllocation = Allocation::find($targetRecord->attribution_allocation_id);
                    $receiverAllocation = Allocation::find($targetRecord->allocation->id);
    
                    if (!$receiverAllocation) {
                        throw new \Exception('Receiver Allocation/Allocation Not Found.');
                    }
    
                    $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
    
                    if (!$qualificationTitle) {
                        throw new \Exception('Qualification Title not found');
                    }
    
                    $numberOfSlots = $data['number_of_slots'] ?? 0;
                    $total_amount = $qualificationTitle->pcc * $numberOfSlots;
    
                    if ($senderAllocation) {
                        $senderAllocation->balance += $targetRecord->total_amount;
                        $senderAllocation->attribution_sent -=  $targetRecord->total_amount;
                        $senderAllocation->save();
    
                        $receiverAllocation->attribution_received -= $targetRecord->total_amount;
                        $receiverAllocation->save();
                    } else {
                        $receiverAllocation->balance += $targetRecord->total_amount;
                        $receiverAllocation->save();
                    }
    
                    $targetRecord->target_status_id = $nonCompliantRecord->id;
                    $targetRecord->save();
    
                    TargetHistory::create([
                        'target_id' => $targetRecord->id,
                        'allocation_id' => $receiverAllocation->id,
                        'attribution_allocation_id' => $senderAllocation ? $senderAllocation->id : null,
                        'tvi_id' => $targetRecord['tvi_id'],
                        'qualification_title_id' => $data['qualification_title_id'],
                        'abdd_id' => $targetRecord['abdd_id'],
                        'number_of_slots' => $targetRecord['number_of_slots'],
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
                        'total_amount' => $targetRecord->total_amount,
                        'appropriation_type' => $targetRecord['appropriation_type'],
                        'target_status_id' => 1,
                        'description' => 'Marked as Non-Compliant'
                    ]);
    
                    // Save or create new remarks for the target
                    NonCompliantRemark::updateOrCreate(
                        [
                            'target_id' => $targetRecord->id,
                            'target_remarks_id' => $data['remarks_id']
                        ],
                        [
                            'others_remarks' => $data['other_remarks'] ?? null,
                        ]
                    );
    
                } else {
                    throw new \Exception('Target already marked as Non-Compliant.');
                }
    
                return $targetRecord;
            });
        } catch (\Exception $e) {
            throw new \Exception("Failed to update target: " . $e->getMessage());
        }
    }
    

  


}
