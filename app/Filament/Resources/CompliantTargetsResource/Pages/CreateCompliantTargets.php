<?php

namespace App\Filament\Resources\CompliantTargetsResource\Pages;

use App\Filament\Resources\CompliantTargetsResource;
use App\Models\Allocation;
use App\Models\ScholarshipProgram;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use App\Services\NotificationHandler;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateCompliantTargets extends CreateRecord
{
    protected static ?string $title = 'Mark as Compliant Target';
    protected static string $resource = CompliantTargetsResource::class;
    protected static ?string $navigationGroup = 'MANAGE TARGET';

    private const COMPLIANT_STATUS_DESC = 'Compliant';

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.compliant-targets.index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Save & Exit'),
            $this->getCancelFormAction()->label('Exit'),
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.targets.index') => 'Pending Targets',
            'Mark as Compliant',
        ];
    }

    protected function handleRecordCreation(array $data): Target
    {
        try {
            return DB::transaction(function () use ($data) {
                $target = Target::find($data['target_id']);
                if (!$target) {
                    throw new Exception("Target not found.");
                }

                $allocation = Allocation::find($target->allocation_id);
                if (!$allocation) {
                    throw new Exception("Allocation for this target does not exist.");
                }

                $compliantStatus = TargetStatus::where('desc', self::COMPLIANT_STATUS_DESC)->first();
                if (!$compliantStatus) {
                    throw new Exception("Target status 'Compliant' not found.");
                }

                $stepScholarship = ScholarshipProgram::where('name', 'STEP')->first();
                if (!$stepScholarship) {
                    throw new Exception("Scholarship program 'STEP' not found.");
                }

                if ($target->qualification_title->scholarship_program_id === $stepScholarship->id) {
                    $this->handleToolkitAvailability($target, $target->allocation->year);
                }

                $target->update(['target_status_id' => $compliantStatus->id]);
                $this->sendSuccessNotification('Target successfully marked as compliant.');

                return $target;
            });
        } catch (Throwable $e) {
            NotificationHandler::handleValidationException('Update Failed', $e->getMessage());
            throw $e;
        }
    }

    private function handleToolkitAvailability($target, int $year): void
    {
        $toolkit = optional($target->qualification_title->toolkits()->where('year', $year)->first());

        if (is_null($toolkit->available_number_of_toolkits)) {
            throw new Exception("Please specify the number of toolkits for '{$target->qualification_title->trainingProgram->title}'.");
        }

        if ($toolkit->available_number_of_toolkits < $target->number_of_slots) {
            throw new Exception("Insufficient toolkits available for this batch.");
        }

        $toolkit->decrement('available_number_of_toolkits', $target->number_of_slots);
    }

    private function sendSuccessNotification(string $message): void
    {
        Notification::make()
            ->title('Success')
            ->success()
            ->body($message)
            ->send();
    }

    private function logTargetHistory($target): void
    {
        TargetHistory::create([
            'target_id' => $target->id,
            'allocation_id' => $target->allocation_id,
            'district_id' => $target->district_id,
            'municipality_id' => $target->municipality_id,
            'tvi_id' => $target->tvi_id,
            'tvi_name' => $target->tvi_name,
            'qualification_title_id' => $target->qualification_title_id,
            'qualification_title_code' => $target->qualification_title_code,
            'qualification_title_soc_code' => $target->qualification_title_soc_code,
            'qualification_title_name' => $target->qualification_title_name,
            'abdd_id' => $target->abdd_id,
            'delivery_mode_id' => $target->delivery_mode_id,
            'learning_mode_id' => $target->learning_mode_id,
            'number_of_slots' => $target->number_of_slots,
            'total_training_cost_pcc' => $target->total_training_cost_pcc,
            'total_cost_of_toolkit_pcc' => $target->total_cost_of_toolkit_pcc,
            'total_training_support_fund' => $target->total_training_support_fund,
            'total_assessment_fee' => $target->total_assessment_fee,
            'total_entrepreneurship_fee' => $target->total_entrepreneurship_fee,
            'total_new_normal_assisstance' => $target->total_new_normal_assisstance,
            'total_accident_insurance' => $target->total_accident_insurance,
            'total_book_allowance' => $target->total_book_allowance,
            'total_uniform_allowance' => $target->total_uniform_allowance,
            'total_misc_fee' => $target->total_misc_fee,
            'total_amount' => $target->total_amount,
            'appropriation_type' => $target->appropriation_type,
            'description' => 'Marked as Compliant',
            'user_id' => Auth::user()->id,
        ]);
    }
}
