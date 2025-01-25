<?php

namespace App\Filament\Resources\CompliantTargetsResource\Pages;

use App\Filament\Resources\CompliantTargetsResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateCompliantTargets extends CreateRecord
{
    protected static ?string $title = 'Mark as Compliant Target';
    protected static string $resource = CompliantTargetsResource::class;
    protected static ?string $navigationGroup = 'MANAGE TARGET';

    private const COMPLIANT_STATUS_DESC = 'Compliant';
    private const DEFAULT_NUMBER_OF_SLOTS = 0;

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.compliant-targets.index');
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
            route('filament.admin.resources.targets.index') => 'Pending Targets',
            'Mark as Compliant',
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $target = $this->findTarget($data['target_id']);
            $allocation = $this->findAllocation($data);
            $compliantStatus = $this->findCompliantStatus();
            $qualificationTitle = $this->findQualificationTitle($data['qualification_title_id']);

            $numberOfSlots = $data['number_of_slots'] ?? self::DEFAULT_NUMBER_OF_SLOTS;
            $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots, $data['allocation_year']);

            $step = ScholarshipProgram::where('name', 'STEP')->first();

            if ($qualificationTitle->scholarship_program_id === $step->id) {
                $costOfToolkitPcc = $qualificationTitle->toolkits()->where('year', $data['allocation_year'])->first();

                if ($costOfToolkitPcc->available_number_of_toolkits === null) {
                    $message = "Please ensure that the number of toolkits for '{$qualificationTitle->trainingProgram->title}' is specified.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }
                elseif ($costOfToolkitPcc->available_number_of_toolkits < $numberOfSlots) {
                    $message = "There are not enough toolkits available for this batch.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }
                else {
                    $costOfToolkitPcc->decrement('available_number_of_toolkits', $numberOfSlots);
                }
            }

            $this->logTargetHistory($target);
            $this->updateTarget($target, $allocation->id, $data, $totals, $compliantStatus->id);

            return $target;
        });
    }

    private function findTarget($targetId): Target
    {
        return Target::findOrFail($targetId);
    }

    private function findAllocation(array $data): Allocation
    {
        $allocation = Allocation::where('attributor_id', $data['sender_legislator_id'])
                ->where('legislator_id', $data['legislator_id'])
                ->where('attributor_particular_id', $data['sender_particular_id'])
                ->where('particular_id', $data['particular_id'])
                ->where('scholarship_program_id', $data['scholarship_program_id'])
                ->where('year', $data['allocation_year'])
                ->first();
    
        return $allocation;
    }

    private function findCompliantStatus(): TargetStatus
    {
        return TargetStatus::where('desc', self::COMPLIANT_STATUS_DESC)->firstOrFail();
    }

    private function findQualificationTitle($qualificationTitleId): QualificationTitle
    {
        return QualificationTitle::findOrFail($qualificationTitleId);
    }

    private function calculateTotals(QualificationTitle $qualificationTitle, int $numberOfSlots, int $year): array
    {
        $quali = QualificationTitle::find($qualificationTitle->id);
        $costOfToolkitPcc = $quali->toolkits()->where('year', $year)->first();


        if (!$quali) {
            $this->sendErrorNotification('Qualification Title not found.');
            throw new Exception('Qualification Title not found.');
        }

        $step = ScholarshipProgram::where('name', 'STEP')->first();

        $totalCostOfToolkit = 0;
        $totalAmount = $qualificationTitle->pcc * $numberOfSlots;
        if ($quali->scholarship_program_id === $step->id) {
            $totalCostOfToolkit = $costOfToolkitPcc->price_per_toolkit * $numberOfSlots;
            $totalAmount += $totalCostOfToolkit;
        }

        return [
            'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
            'total_cost_of_toolkit_pcc' => $totalCostOfToolkit,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
            'total_new_normal_assistance' => $qualificationTitle->new_normal_assistance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => $totalAmount,
        ];
    }

    private function logTargetHistory(Target $target): void
    {
        TargetHistory::create([
            'target_id' => $target->id,
            'allocation_id' => $target['allocation_id'],
            'district_id' => $target['district_id'],
            'municipality_id' => $target['municipality_id'],
            'tvi_id' => $target['tvi_id'],
            'tvi_name' => $target['tvi_name'],
            'qualification_title_id' => $target['qualification_title_id'],
            'qualification_title_code' => $target['qualification_title_code'],
            'qualification_title_soc_code' => $target['qualification_title_soc_code'],
            'qualification_title_name' => $target['qualification_title_name'],
            'abdd_id' => $target['abdd_id'],
            'delivery_mode_id' => $target['delivery_mode_id'],
            'learning_mode_id' => $target['learning_mode_id'],
            'number_of_slots' => $target['number_of_slots'],
            'total_training_cost_pcc' => $target['total_training_cost_pcc'],
            'total_cost_of_toolkit_pcc' => $target['total_cost_of_toolkit_pcc'],
            'total_training_support_fund' => $target['total_training_support_fund'],
            'total_assessment_fee' => $target['total_assessment_fee'],
            'total_entrepreneurship_fee' => $target['total_entrepreneurship_fee'],
            'total_new_normal_assisstance' => $target['total_new_normal_assisstance'],
            'total_accident_insurance' => $target['total_accident_insurance'],
            'total_book_allowance' => $target['total_book_allowance'],
            'total_uniform_allowance' => $target['total_uniform_allowance'],
            'total_misc_fee' => $target['total_misc_fee'],
            'total_amount' => $target['total_amount'],
            'appropriation_type' => $target['appropriation_type'],
            'description' => 'Marked as Compliant',
            'user_id' => Auth::user()->id,
        ]);
    }

    private function updateTarget(Target $target, int $allocationId, array $data, array $totals, int $statusId): void
    {
        $target->update(array_merge($data, $totals, [
            'allocation_id' => $allocationId,
            'target_status_id' => $statusId,
        ]));
    }

    private function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Error')
            ->danger()
            ->body($message)
            ->send();
    }
}
