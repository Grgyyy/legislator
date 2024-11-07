<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\TargetHistory;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAttributionTarget extends EditRecord
{
    protected static string $resource = AttributionTargetResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.attribution-targets.edit', ['record' => $this->record->id]) => 'Attribution Target',
            'Edit'
        ];
    }

    protected ?string $heading = 'Edit Attribution Target';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $senderAllocation = $record->allocation;
        $receiverAllocation = $record->attributionAllocation;

        $data['attribution_sender'] = $senderAllocation->legislator_id ?? null;
        $data['attribution_sender_particular'] = $senderAllocation->particular_id ?? null;
        $data['attribution_scholarship_program'] = $senderAllocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $senderAllocation->year ?? null;
        $data['attribution_appropriation_type'] = $record['appropriation_type'];
        $data['attribution_receiver'] = $receiverAllocation->legislator_id ?? null;
        $data['attribution_receiver_particular'] = $receiverAllocation->particular_id ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $senderAllocation = Allocation::where('legislator_id', $data['attribution_sender'])
                ->where('particular_id', $data['attribution_sender_particular'])
                ->where('scholarship_program_id', $data['attribution_scholarship_program'])
                ->where('year', $data['allocation_year'])
                ->first();

            if (!$senderAllocation) {
                throw new \Exception('Attribution Sender Allocation not found');
            }

            $receiverAllocation = Allocation::where('legislator_id', $data['attribution_receiver']) 
                ->where('particular_id', $data['attribution_receiver_particular'])
                ->where('scholarship_program_id', $data['attribution_scholarship_program'])
                ->where('year', $data['allocation_year'])
                ->first();

            if (!$receiverAllocation) {
                throw new \Exception('Attribution Sender Allocation not found');
            }

            $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);

            if (!$qualificationTitle) {
                throw new \Exception('Qualification Title not found');
            }

            $numberOfSlots = $data['number_of_slots'] ?? 0;
            $total_amount = $qualificationTitle->pcc * $numberOfSlots;

            $senderAllocation->balance += $record['total_amount'];
            $senderAllocation->attribution_sent -= $record['total_amount'];
            $senderAllocation->save();

            $receiverAllocation->attribution_received -= $record['total_amount'];
            $receiverAllocation->save();

            if ($senderAllocation->balance >= $total_amount) { 
                $senderAllocation->balance -= $total_amount;
                $senderAllocation->attribution_sent += $total_amount;
                $senderAllocation->save();

                $receiverAllocation->attribution_received += $total_amount;
                $receiverAllocation->save();

                TargetHistory::create([
                    'target_id' => $record->id,
                    'allocation_id' => $senderAllocation->id,
                    'attribution_allocation_id' => $receiverAllocation->id,
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
                    'total_amount' => $total_amount,
                    'appropriation_type' => $data['attribution_appropriation_type'],
                    'target_status_id' => 1,
                    'description' => 'Target Edited',
                ]);

                $record->update([
                    'allocation_id' => $senderAllocation->id,
                    'attribution_allocation_id' => $receiverAllocation->id,
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
                    'total_amount' => $total_amount,
                    'appropriation_type' => $data['attribution_appropriation_type'],
                    'target_status_id' => 1,
                ]);
                
                return $record;
            }
            else {
                throw new \Exception('Insufficient balance for allocation');
            }
        });
    }
}
