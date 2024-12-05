<?php

namespace App\Filament\Resources\AttributionTargetResource\Pages;

use App\Filament\Resources\AttributionTargetResource;
use App\Models\Allocation;
use App\Models\ProvinceAbdd;
use App\Models\QualificationTitle;
use App\Models\TargetHistory;
use App\Models\Tvi;
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
        $senderAllocation = $record->attributionAllocation;
        $receiverAllocation = $record->allocation;

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
            $requiredFields = [
                'attribution_sender', 'attribution_sender_particular', 'attribution_scholarship_program',
                'allocation_year', 'attribution_appropriation_type', 'attribution_receiver', 'attribution_receiver_particular',
                'tvi_id', 'qualification_title_id', 'abdd_id', 'number_of_slots', 'learning_mode_id',
            ];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("The field '$field' is required.");
                }
            }

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
                $receiverAllocation = Allocation::create([
                    'soft_or_commitment' => 'Soft',
                    'legislator_id' => $data['attribution_receiver'],
                    'particular_id' => $data['attribution_receiver_particular'],
                    'scholarship_program_id' => $data['attribution_scholarship_program'],
                    'allocation' => 0,
                    'balance' => 0,
                    'year' => $data['allocation_year'],
                ]);
            }

            $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
            if (!$qualificationTitle) {
                throw new \Exception('Qualification Title not found');
            }

            $numberOfSlots = $data['number_of_slots'] ?? 0;

            $total_training_cost_pcc = $qualificationTitle->training_cost_pcc * $numberOfSlots;
            $total_cost_of_toolkit_pcc = $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots;
            $total_training_support_fund = $qualificationTitle->training_support_fund * $numberOfSlots;
            $total_assessment_fee = $qualificationTitle->assessment_fee * $numberOfSlots;
            $total_entrepreneurship_fee = $qualificationTitle->entrepreneurship_fee * $numberOfSlots;
            $total_new_normal_assisstance = $qualificationTitle->new_normal_assisstance * $numberOfSlots;
            $total_accident_insurance = $qualificationTitle->accident_insurance * $numberOfSlots;
            $total_book_allowance = $qualificationTitle->book_allowance * $numberOfSlots;
            $total_uniform_allowance = $qualificationTitle->uniform_allowance * $numberOfSlots;
            $total_misc_fee = $qualificationTitle->misc_fee * $numberOfSlots;
            $admin_cost = $data['admin_cost'] ?? 0;

            $total_amount = ($qualificationTitle->pcc * $numberOfSlots) + $admin_cost;

            $institution = Tvi::find($data['tvi_id']);
            if (!$institution) {
                throw new \Exception('Institution not found');
            }

            if ($senderAllocation->balance + $record->total_amount < $total_amount) {
                throw new \Exception('Insufficient funds in sender allocation');
            }

            $provinceAbdd = ProvinceAbdd::find($data['abdd_id']);
            if (!$provinceAbdd) {
                throw new \Exception('ProvinceAbdd entry not found');
            }

            if ($provinceAbdd->available_slots + $record->number_of_slots < $numberOfSlots) {
                throw new \Exception('Not enough available slots in ProvinceAbdd');
            }

            $senderAllocation->balance += $record->total_amount;
            $senderAllocation->attribution_sent -= $record->total_amount;
            $senderAllocation->save();

            $receiverAllocation->attribution_received -= $record->total_amount;
            $receiverAllocation->save();

            $provinceAbdd->increment('available_slots', $record->number_of_slots);

            $record->update([
                'abscap_id' => $data['abscap_id'],
                'allocation_id' => $receiverAllocation->id,
                'attribution_allocation_id' => $senderAllocation->id,
                'tvi_id' => $institution->id,
                'tvi_name' => $institution->name,
                'municipality_id' => $institution->municipality_id,
                'district_id' => $institution->district_id,
                'qualification_title_id' => $qualificationTitle->id,
                'qualification_title_code' => $qualificationTitle->trainingProgram->code ?? null,
                'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                'learning_mode_id' => $data['learning_mode_id'],
                'abdd_id' => $data['abdd_id'],
                'number_of_slots' => $numberOfSlots,
                'total_training_cost_pcc' => $total_training_cost_pcc,
                'total_cost_of_toolkit_pcc' => $total_cost_of_toolkit_pcc,
                'total_training_support_fund' => $total_training_support_fund,
                'total_assessment_fee' => $total_assessment_fee,
                'total_entrepreneurship_fee' => $total_entrepreneurship_fee,
                'total_new_normal_assisstance' => $total_new_normal_assisstance,
                'total_accident_insurance' => $total_accident_insurance,
                'total_book_allowance' => $total_book_allowance,
                'total_uniform_allowance' => $total_uniform_allowance,
                'total_misc_fee' => $total_misc_fee,
                'admin_cost' => $admin_cost,
                'total_amount' => $total_amount,
                'appropriation_type' => $data['attribution_appropriation_type'],
                'target_status_id' => 1,
            ]);

            $senderAllocation->balance -= $total_amount;
            $senderAllocation->attribution_sent += $total_amount;
            $senderAllocation->save();

            $receiverAllocation->attribution_received += $total_amount;
            $receiverAllocation->save();

            $provinceAbdd->decrement('available_slots', $numberOfSlots);

            return $record;
        });
    }
}
