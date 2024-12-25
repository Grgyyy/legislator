<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\NonCompliantRemark;
use App\Models\ProvinceAbdd;
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
                // Validate and find target record
                $targetRecord = Target::find($data['target_id']);
                if (!$targetRecord) {
                    throw new \Exception('Target not found.');
                }

                // Find the "Non-Compliant" status record
                $nonCompliantRecord = TargetStatus::where('desc', self::COMPLIANT_STATUS_DESC)->first();
                if (!$nonCompliantRecord) {
                    throw new \Exception('Compliant status not found.');
                }

                // Ensure the target has not already been marked as Non-Compliant
                if ($targetRecord->targetStatus->desc === 'Non-Compliant') {
                    throw new \Exception('Target already marked as Non-Compliant.');
                }

                // Handle resource adjustments
                $this->adjustResources($targetRecord, $data);

                // Update target status to Non-Compliant
                $targetRecord->target_status_id = $nonCompliantRecord->id;
                $targetRecord->save();

                // Log target history
                $this->logTargetHistory($targetRecord, $data);

                // Save non-compliant remarks
                NonCompliantRemark::updateOrCreate(
                    [
                        'target_id' => $targetRecord->id,
                        'target_remarks_id' => $data['remarks_id']
                    ],
                    [
                        'others_remarks' => $data['other_remarks'] ?? null,
                    ]
                );

                return $targetRecord;
            });
        } catch (\Exception $e) {
            throw new \Exception("Failed to update target: " . $e->getMessage());
        }
    }

    private function adjustResources(Target $targetRecord, array $data): void
    {
        $senderAllocation = Allocation::find($targetRecord->attribution_allocation_id);
        $receiverAllocation = Allocation::find($targetRecord->allocation->id);
        $provinceAbdd = ProvinceAbdd::where('province_id', $targetRecord->district->province_id)
            ->where('abdd_id', $targetRecord->abdd_id)
            ->where('year', $data['allocation_year'])
            ->first();

        if (!$receiverAllocation) {
            throw new \Exception('Receiver Allocation/Allocation Not Found.');
        }

        $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
        if (!$qualificationTitle) {
            throw new \Exception('Qualification Title not found');
        }

        if (!$provinceAbdd) {
            throw new \Exception('Province ABDD Slots not found');
        }

        $numberOfSlots = $targetRecord['number_of_slots'];

        if ($senderAllocation) {
            // Revert sender allocation balances
            $senderAllocation->balance += $targetRecord->total_amount;
            $senderAllocation->attribution_sent -= $targetRecord->total_amount;
            $senderAllocation->save();

            // Adjust receiver allocation
            $receiverAllocation->attribution_received -= $targetRecord->total_amount;
            $receiverAllocation->save();
        } else {
            // Adjust receiver allocation if no sender allocation exists
            $receiverAllocation->balance += $targetRecord->total_amount;
            $receiverAllocation->save();
        }

        if ($provinceAbdd) {
            // Ensure available_slots is initialized
            $provinceAbdd->available_slots = $provinceAbdd->available_slots ?? 0;

            // Update province slots
            $provinceAbdd->available_slots += $numberOfSlots;
            $provinceAbdd->save();
        } else {
            throw new \Exception('Province ABDD record not found.');
        }
    }

    private function logTargetHistory(Target $targetRecord, array $data): void
    {
        TargetHistory::create([
            'abscap_id' => $targetRecord['abscap_id'],
            'target_id' => $targetRecord->id,
            'allocation_id' => $targetRecord['allocation_id'],
            'district_id' => $targetRecord['district_id'],
            'municipality_id' => $targetRecord['municipality_id'],
            'attribution_allocation_id' => $targetRecord['attribution_allocation_id'],
            'tvi_id' => $targetRecord['tvi_id'],
            'tvi_name' => $targetRecord['tvi_name'],
            'qualification_title_id' => $targetRecord['qualification_title_id'],
            'qualification_title_code' => $targetRecord['qualification_title_code'],
            'qualification_title_name' => $targetRecord['qualification_title_name'],
            'abdd_id' => $targetRecord['abdd_id'],
            'delivery_mode_id' => $targetRecord['delivery_mode_id'],
            'learning_mode_id' => $targetRecord['learning_mode_id'],
            'number_of_slots' => $targetRecord['number_of_slots'],
            'total_training_cost_pcc' => $targetRecord['total_training_cost_pcc'],
            'total_cost_of_toolkit_pcc' => $targetRecord['total_cost_of_toolkit_pcc'],
            'total_training_support_fund' => $targetRecord['total_training_support_fund'],
            'total_assessment_fee' => $targetRecord['total_assessment_fee'],
            'total_entrepreneurship_fee' => $targetRecord['total_entrepreneurship_fee'],
            'total_new_normal_assisstance' => $targetRecord['total_new_normal_assisstance'],
            'total_accident_insurance' => $targetRecord['total_accident_insurance'],
            'total_book_allowance' => $targetRecord['total_book_allowance'],
            'total_uniform_allowance' => $targetRecord['total_uniform_allowance'],
            'total_misc_fee' => $targetRecord['total_misc_fee'],
            'total_amount' => $targetRecord['total_amount'],
            'appropriation_type' => $targetRecord['appropriation_type'],
            'description' => 'Marked as Non-Compliant'
        ]);
    }
}
