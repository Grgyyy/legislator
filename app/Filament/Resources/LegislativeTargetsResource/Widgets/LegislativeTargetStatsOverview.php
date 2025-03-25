<?php
namespace App\Filament\Resources\LegislativeTargetsResource\Widgets;
use App\Models\Allocation;
use App\Models\Target;
use App\Models\TargetStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegislativeTargetStatsOverview extends BaseWidget
{
    public ?int $legislatorId = null;

    public ?int $scholarshipProgramId = null;

    public ?int $allocationId = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getCards(): array
    {
        $totalAllocation = $this->getTotalAllocation($this->legislatorId, $this->scholarshipProgramId);
        $adminCost = $this->calculateAdminCost($totalAllocation);
        $trainingCost = $this->getTotalTrainingCost($this->allocationId);
        $costOfToolkits = $this->getTotalCostOfToolkits($this->allocationId);

        Log::info('Legislator ID: ' . $this->legislatorId);
        Log::info('Scholarship Program ID: ' . $this->scholarshipProgramId);

        return [
            Stat::make('Total Allocation', '₱ ' . number_format($totalAllocation))
                ->description('Admin Cost: ₱ ' . number_format($adminCost))
                ->color('info'),
            Stat::make('Training Cost', '₱ ' . number_format($trainingCost))
                ->description('Total training cost')
                ->color('warning'),
            Stat::make('Cost of Toolkits', '₱ ' . number_format($costOfToolkits))
                ->description('Total cost of toolkits')
                ->color('warning'),
        ];
    }
    
    protected function getTotalAllocation($legislatorId, $scholarshipProgramId): float
    {
        return Allocation::where('id', $legislatorId)
            ->where('id', $scholarshipProgramId)
            ->sum('allocation');
    }

    protected function calculateAdminCost($totalAllocation): float
    {
        return $totalAllocation * 0.02;
    }

    protected function getTotalTrainingCost($allocationId): float
    {
        $compliantStatus = TargetStatus::where('desc', 'Compliant')->value('id');

        return Target::where('allocation_id', $allocationId)
            ->where('target_status_id', '=', $compliantStatus)
            ->sum(DB::raw('total_training_cost_pcc + total_training_support_fund + total_assessment_fee'));
    }

    protected function getTotalCostOfToolkits($allocationId): float
    {
        $compliantStatus = TargetStatus::where('desc', 'Compliant')->value('id');
        
        return Target::where('allocation_id', $allocationId)
            ->where('target_status_id', '=', $compliantStatus)
            ->sum(DB::raw('total_cost_of_toolkit_pcc'));
    }
}
