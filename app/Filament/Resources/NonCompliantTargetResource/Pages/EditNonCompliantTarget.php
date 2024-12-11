<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use App\Models\Allocation;
use App\Models\ProvinceAbdd;
use App\Models\QualificationScholarship;
use App\Models\QualificationTitle;
use App\Models\TargetHistory;
use App\Models\Tvi;
use DB;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

        if ($attributionAllocation) {
            return route('filament.admin.resources.attribution-targets.index');
        } else {
            return route('filament.admin.resources.targets.index');
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
    
        // Check if 'attributionAllocation' exists and set values accordingly
        $data['sender_legislator_id'] = $record->attributionAllocation->legislator_id ?? null;
        $data['sender_particular_id'] = $record->attributionAllocation->particular_id ?? null;
    
        // Use null coalescing operator to safely assign values
        $data['receiver_legislator_id'] = $record->allocation->legislator_id ?? null;
        $data['receiver_particular_id'] = $record->allocation->particular_id ?? null;
    
        // Proceed with other data assignments
        $data['scholarship_program_id'] = $record->allocation->scholarship_program_id ?? null;
        $data['allocation_year'] = $record->allocation->year ?? null;
        $data['target_id'] = $record->id ?? null;
    
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return DB::transaction(function () use ($record, $data) {
                // Retrieve sender and receiver allocations
                $senderLegislatorId = $data['sender_legislator_id'];
                $senderParticularId = $data['sender_particular_id'];
                $receiverLegislatorId = $record->allocation->legislator_id;
                $receiverParticularId = $record->allocation->particular_id;

                // throw new \Exception($receiverLegislatorId);

                // Validate that receiver particular_id is not null
                if (is_null($receiverParticularId)) {
                    Log::error('Receiver Particular ID is null', ['receiver_legislator_id' => $receiverLegislatorId]);
                    throw new \Exception('Receiver Particular ID cannot be null');
                }

                // Check if the receiver allocation exists, if not, create one
                $receiverAllocation = Allocation::where('legislator_id', $receiverLegislatorId)
                    ->where('particular_id', $receiverParticularId)
                    ->where('scholarship_program_id', $data['scholarship_program_id'] ?? null)
                    ->where('year', $data['allocation_year'] ?? null)
                    ->first();

                if (!$receiverAllocation) {
                    // Log and handle the missing receiver allocation
                    Log::warning('Receiver Allocation not found', [
                        'receiver_legislator_id' => $receiverLegislatorId,
                        'receiver_particular_id' => $receiverParticularId,
                        'scholarship_program_id' => $data['scholarship_program_id'],
                        'allocation_year' => $data['allocation_year']
                    ]);
                    
                    // Optionally, create a new receiver allocation if needed
                    $receiverAllocation = Allocation::create([
                        'legislator_id' => $receiverLegislatorId,
                        'particular_id' => $receiverParticularId,
                        'scholarship_program_id' => $data['scholarship_program_id'],
                        'year' => $data['allocation_year'],
                        'balance' => 0,  // Set default balance if necessary
                    ]);
                }

                // Retrieve sender allocation
                $senderAllocation = Allocation::where('legislator_id', $senderLegislatorId)
                    ->where('particular_id', $senderParticularId)
                    ->where('scholarship_program_id', $data['scholarship_program_id'] ?? null)
                    ->where('year', $data['allocation_year'] ?? null)
                    ->first();

                if (!$senderAllocation) {
                    Log::error('Sender Allocation not found', ['sender_legislator_id' => $senderLegislatorId]);
                    throw new \Exception('Sender Allocation not found');
                }

                $qualificationTitle = QualificationTitle::find($data['qualification_title_id']);
                if (!$qualificationTitle) {
                    Log::error('Qualification Title not found', ['qualification_title_id' => $data['qualification_title_id']]);
                    throw new \Exception('Qualification Title not found');
                }

                $institution = Tvi::find($data['tvi_id']);
                if (!$institution) {
                    Log::error('Institution not found', ['tvi_id' => $data['tvi_id']]);
                    throw new \Exception('Institution not found');
                }

                $provinceAbdd = ProvinceAbdd::where('province_id', $institution->district->province_id)
                    ->where('abdd_id', $data['abdd_id'] ?? null)
                    ->where('year', $data['allocation_year'] ?? null)
                    ->first();

                if (!$provinceAbdd) {
                    Log::error('Province ABDD not found', $data);
                    throw new \Exception('Province ABDD not found');
                }

                $numberOfSlots = $data['number_of_slots'] ?? 0;
                $totalAmount = $qualificationTitle->pcc * $numberOfSlots;

                // Check if allocation has enough balance
                $allocationUsing = $senderAllocation ?? $receiverAllocation;

                if ($allocationUsing && $allocationUsing->balance >= $totalAmount) {
                    if ($senderAllocation) {
                        // Update sender allocation balance and attributes
                        $senderAllocation->balance -= $totalAmount;
                        $senderAllocation->attribution_sent += $totalAmount;
                        $senderAllocation->save();

                        // Update receiver allocation balance
                        $receiverAllocation->attribution_received += $totalAmount;
                        $receiverAllocation->save();
                    } else {
                        // Only update receiver if sender allocation is null
                        $receiverAllocation->balance -= $totalAmount;
                        $receiverAllocation->save();
                    }

                    // Update Province ABDD available slots
                    $provinceAbdd->available_slots -= $numberOfSlots;
                    $provinceAbdd->save();

                    // Create Target History record
                    TargetHistory::create([
                        'abscap_id' => $data['abscap_id'] ?? null,
                        'target_id' => $record->id,
                        'allocation_id' => $record->allocation->id,
                        'district_id' => $institution->district_id,
                        'municipality_id' => $institution->municipality_id,
                        'attribution_allocation_id' => $record->attribution_allocation_id,
                        'tvi_id' => $institution->id,
                        'tvi_name' => $institution->name,
                        'qualification_title_id' => $qualificationTitle->id,
                        'qualification_title_code' => $qualificationTitle->trainingProgram->code,
                        'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                        'abdd_id' => $data['abdd_id'],
                        'delivery_mode_id' => $data['delivery_mode_id'] ?? null,
                        'learning_mode_id' => $data['learning_mode_id'] ?? null,
                        'number_of_slots' => $data['number_of_slots'] ?? 0,
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
                        'admin_cost' => $data['admin_cost'] ?? 0,
                        'appropriation_type' => $data['appropriation_type'],
                        'total_amount' => ($qualificationTitle->pcc * $numberOfSlots) + $data['admin_cost'],
                        'description' => 'Target Modified'
                    ]);
                }

                // Return the updated record
                return $record;
            });
        } catch (\Exception $e) {
            // Handle exceptions and log errors
            Log::error('Error updating record', ['exception' => $e]);
            throw $e;
        }
    }
}
