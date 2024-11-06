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

    protected ?string $heading = 'Edit Pending Target';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.edit', ['record' => $this->record->id]) => 'Pending Target',
            'Edit '
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;
        $data['legislator_id'] = $allocation->legislator_id ?? null;
        $data['particular_id'] = $allocation->particular_id ?? null;
        $data['scholarship_program_id'] = $allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $allocation->year ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
{
    return DB::transaction(function () use ($record, $data) {
        // Find allocation based on provided data
        $allocation = Allocation::where('legislator_id', $data['legislator_id'])
            ->where('particular_id', $data['particular_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->where('year', $data['allocation_year'])
            ->first();

        if (!$allocation) {
            throw new \Exception('Allocation not found for the provided legislator and scholarship program.');
        }

        $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
        if (!$qualificationTitle) {
            throw new \Exception('Qualification Title not found');
        }

        $numberOfSlots = $data['number_of_slots'] ?? 0;
        $total_amount = $qualificationTitle->pcc * $numberOfSlots;

        $allocation->balance += $record['total_amount'];
        $allocation->save();
        
        if ($allocation->balance >= $total_amount) {
            $allocation->balance -= $total_amount;
            $allocation->save();

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
                'total_amount' => $total_amount,
                'appropriation_type' => $data['appropriation_type'],
                'target_status_id' => 1,
                'description' => 'Target Edited',
            ]);

            $record->update([
                'allocation_id' => $allocation->id,
                'tvi_id' => $data['tvi_id'],
                'qualification_title_id' => $data['qualification_title_id'],
                'abdd_id' => $data['abdd_id'],
                'number_of_slots' => $data['number_of_slots'],
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
