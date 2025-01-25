<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\NonCompliantRemark;
use App\Models\ProvinceAbdd;
use App\Models\QualificationTitle;
use App\Models\SkillPriority;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class CreateNonCompliantTarget extends CreateRecord
{
    protected static ?string $title = 'Mark as Non-Compliant Target';

    protected static string $resource = NonCompliantTargetResource::class;

    private const COMPLIANT_STATUS_DESC = 'Non-Compliant';

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

                if ($targetRecord->targetStatus->desc === 'Non-Compliant') {
                    throw new \Exception('Target already marked as Non-Compliant.');
                }

                $this->adjustResources($targetRecord, $data);

                $targetRecord->target_status_id = $nonCompliantRecord->id;
                $targetRecord->save();

                $this->logTargetHistory($targetRecord, $data);

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
        $allocation = Allocation::find($targetRecord->allocation_id);

        $previousSkillPrio = SkillPriority::where([
            'training_program_id' => $targetRecord->qualification_title->training_program_id,
            'province_id' => $targetRecord->tvi->district->province_id,
            'year' => $targetRecord->allocation->year,
        ]);

        if (!$allocation) {
            throw new \Exception('Allocation Not Found.');
        }

        $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
        if (!$qualificationTitle) {
            throw new \Exception('Qualification Title not found');
        }

        if (!$previousSkillPrio) {
            throw new \Exception('Skills Priority not found');
        }

        $numberOfSlots = $targetRecord['number_of_slots'];

        $allocation->balance += $targetRecord->total_amount;
        $allocation->save();

        $previousSkillPrio->increment('available_slots', $targetRecord->number_of_slots);
    }

    private function logTargetHistory(Target $targetRecord, array $data): void
    {
        TargetHistory::create([
            'target_id' => $targetRecord->id,
            'allocation_id' => $targetRecord['allocation_id'],
            'district_id' => $targetRecord['district_id'],
            'municipality_id' => $targetRecord['municipality_id'],
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
            'description' => 'Marked as Non-Compliant',
            'user_id' => Auth::user()->id,
        ]);
    }
}
