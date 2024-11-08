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
        $record = $this->record;
        $attributionAllocation = $record->attribution_allocation_id;

        if($attributionAllocation) {
            return route('filament.admin.resources.attribution-targets.index');
        }
        else 
        {
            return route('filament.admin.resources.targets.index');
        }
        
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;

        $data['sender_legislator_id'] = $record->attributionAllocation->legislator_id ?? null;
        $data['sender_particular_id'] = $record->attributionAllocation->particular_id ?? null;
        $data['receiver_legislator_id'] = $record->allocation->legislator_id ?? null;
        $data['receiver_particular_id'] = $record->allocation->particular_id ?? null;


        $data['scholarship_program_id'] = $record->allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $record->allocation->year ?? null;
        $data['target_id'] = $record->id ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
           
            $senderAllocation = Allocation::where('legislator_id', $data['sender_legislator_id'])
            ->where('particular_id', $data['sender_particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['allocation_year'])
            ->first();

            $receiverAllocation = Allocation::where('legislator_id', $data['receiver_legislator_id']) 
                ->where('particular_id', $data['receiver_particular_id'])
                ->where('scholarship_program_id', $data['scholarship_program_id'])
                ->where('year', $data['allocation_year'])
                ->first();

            if (!$receiverAllocation) {
                throw new \Exception('Attribution Sender Allocation not found');
            }

            
            if (!$receiverAllocation) {
                throw new \Exception('Attribution Sender Allocation not found');
            }

            $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);

            if (!$qualificationTitle) {
                throw new \Exception('Qualification Title not found');
            }

            $numberOfSlots = $data['number_of_slots'] ?? 0;
            $total_amount = $qualificationTitle->pcc * $numberOfSlots;


            if ($senderAllocation) {
                $senderAllocation->balance += $record['total_amount'];
                $senderAllocation->attribution_sent -= $record['total_amount'];
                $senderAllocation->save();

                $receiverAllocation->attribution_received -= $record['total_amount'];
                $receiverAllocation->save();
            }
            else {
                $receiverAllocation->balance += $record['total_amount'];
                $receiverAllocation->save();
            }

            $allocationUsing = $senderAllocation ? $senderAllocation : $receiverAllocation;

            if ($allocationUsing->balance >- $total_amount) {
                if ($senderAllocation) {
                    $senderAllocation->balance -= $total_amount;
                    $senderAllocation->attribution_sent += $total_amount;
                    $senderAllocation->save();

                    $receiverAllocation->attribution_received += $total_amount;
                    $receiverAllocation->save();
                }
                else {
                    $receiverAllocation->balance -= $total_amount;
                    $receiverAllocation->save();
                }


                TargetHistory::create([
                    'target_id' => $record->id,
                    'allocation_id' => $receiverAllocation->id,
                    'attribution_allocation_id' => $senderAllocation ? $senderAllocation->id : null,
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
                    'appropriation_type' => $data['appropriation_type'],
                    'target_status_id' => 1,
                    'description' => 'Target Edited'
                ]);


                $record->update([
                    'allocation_id' => $receiverAllocation->id,
                    'attribution_allocation_id' =>  $senderAllocation ? $senderAllocation->id : null,
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
                    'appropriation_type' => $data['appropriation_type'],
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
