<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\TargetHistory;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTarget extends EditRecord
{
    protected static string $resource = TargetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormSchema(): array {
        return [
            Forms\Components\TextInput::make('name')
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;
        $legislatorId = $allocation->legislator_id ?? null;
        $particularId = $allocation->particular_id ?? null;
        $scholarshipId = $allocation->scholarship_program_id ?? null;
        $allocationYear = $allocation->year ?? null;

        $data['legislator_id'] = $data['legislator_id'] ?? $legislatorId;
        $data['particular_id'] = $data['particularId'] ?? $particularId;
        $data['scholarship_program_id'] = $data['scholarship_program_id'] ?? $scholarshipId;
        $data['allocation_year'] = $data['allocation_year'] ?? $allocationYear;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $allocation = Allocation::where('legislator_id', $data['legislator_id'])
                ->where('particular_id', $data['particular_id'])
                ->where('scholarship_program_id', $data['scholarship_program_id'])
                ->where('year', $data['allocation_year'])
                ->first();

            if (!$allocation) {
                throw new \Exception('Allocation not found for the provided legislator and scholarship program.');
            }

            TargetHistory::create([
                'target_id' => $record->id,
                'allocation_id' => $record->allocation_id,
                'tvi_id' => $record->tvi_id,
                'qualification_title_id' => $record->qualification_title_id,
                'abdd_id' => $record->abdd_id,
                'number_of_slots' => $record->number_of_slots,
                'total_training_cost_pcc' => $record->total_training_cost_pcc,
                'total_cost_of_toolkit_pcc' => $record->total_cost_of_toolkit_pcc,
                'total_training_support_fund' => $record->total_training_support_fund,
                'total_assessment_fee' => $record->total_assessment_fee,
                'total_entrepeneurship_fee' => $record->total_entrepeneurship_fee,
                'total_new_normal_assisstance' => $record->total_new_normal_assisstance,
                'total_accident_insurance' => $record->total_accident_insurance,
                'total_book_allowance' => $record->total_book_allowance,
                'total_uniform_allowance' => $record->total_uniform_allowance,
                'total_misc_fee' => $record->total_misc_fee,
                'total_amount' => $record->total_amount,
                'appropriation_type' => $record->appropriation_type,
                'target_status_id' => $record->target_status_id,
            ]);

            $existingTotalAmount = $record->total_amount ?? 0;
            $allocation->balance += $existingTotalAmount;

            $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
            if (!$qualificationTitle) {
                throw new \Exception('Qualification Title not found');
            }

            $numberOfSlots = $data['number_of_slots'] ?? 0;

            $total_training_cost_pcc = $qualificationTitle->training_cost_pcc * $numberOfSlots;
            $total_cost_of_toolkit_pcc = $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots;
            $total_training_support_fund = $qualificationTitle->training_support_fund * $numberOfSlots;
            $total_assessment_fee = $qualificationTitle->assessment_fee * $numberOfSlots;
            $total_entrepeneurship_fee = $qualificationTitle->entrepeneurship_fee * $numberOfSlots;
            $total_new_normal_assisstance = $qualificationTitle->new_normal_assisstance * $numberOfSlots;
            $total_accident_insurance = $qualificationTitle->accident_insurance * $numberOfSlots;
            $total_book_allowance = $qualificationTitle->book_allowance * $numberOfSlots;
            $total_uniform_allowance = $qualificationTitle->uniform_allowance * $numberOfSlots;
            $total_misc_fee = $qualificationTitle->misc_fee * $numberOfSlots;
            $total_amount = $qualificationTitle->pcc * $numberOfSlots;

            if ($allocation->balance >= $total_amount) {

                $allocation->balance -= $total_amount;
                $allocation->save();

                $record->update([
                    'allocation_id' => $allocation->id,
                    'tvi_id' => $data['tvi_id'],
                    'qualification_title_id' => $data['qualification_title_id'],
                    'abdd_id' => $data['abdd_id'],
                    'number_of_slots' => $data['number_of_slots'],
                    'total_training_cost_pcc' => $total_training_cost_pcc,
                    'total_cost_of_toolkit_pcc' => $total_cost_of_toolkit_pcc,
                    'total_training_support_fund' => $total_training_support_fund,
                    'total_assessment_fee' => $total_assessment_fee,
                    'total_entrepeneurship_fee' => $total_entrepeneurship_fee,
                    'total_new_normal_assisstance' => $total_new_normal_assisstance,
                    'total_accident_insurance' => $total_accident_insurance,
                    'total_book_allowance' => $total_book_allowance,
                    'total_uniform_allowance' => $total_uniform_allowance,
                    'total_misc_fee' => $total_misc_fee,
                    'total_amount' => $total_amount,
                    'appropriation_type' => $data['appropriation_type'],
                    'target_status_id' => 1,
                ]);

                return $record;
            } else {
                throw new \Exception('Insufficient balance for allocation');
            }
        });
    }

}
