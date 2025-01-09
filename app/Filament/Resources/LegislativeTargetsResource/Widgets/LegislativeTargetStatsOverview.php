<?php
namespace App\Filament\Resources\LegislativeTargetsResource\Widgets;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Target;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class LegislativeTargetStatsOverview extends BaseWidget
{
    public ?int $legislatorId = null;
    public ?int $allocationId = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getCards(): array
    {
        $totalAllocation = $this->getTotalAllocation($this->legislatorId);
        $adminCost = $this->calculateAdminCost($totalAllocation);
        $trainingCost = $this->getTotalTrainingCost($this->allocationId);
        $costOfToolkits = $this->getTotalCostOfToolkits($this->allocationId);
        
        Log::info('Legislator ID: ' . $this->legislatorId);

        return [
            Stat::make('Total Allocation', '₱ ' . number_format($totalAllocation))
                ->description('Admin Cost: ₱ ' . number_format($adminCost))
                ->color('info'), //might change if attribution is included or not in the report
            Stat::make('Training Cost', '₱ ' . number_format($trainingCost))
                ->description('Total training cost')
                ->color('warning'),
            Stat::make('Cost of Toolkits', '₱ ' . number_format($costOfToolkits))
                ->description('Total cost of toolkits')
                ->color('warning'),
        ];
    }
    protected function getTotalAllocation($legislatorId): float
    {
        return Allocation::where('id', $legislatorId)
            ->sum('allocation');
    }

    protected function calculateAdminCost($totalAllocation): float
    {
        return $totalAllocation * 0.02;
    }

    protected function getTotalTrainingCost($allocationId): float
    {
        return Target::where('allocation_id' , $allocationId)
            ->sum(DB::raw('total_training_cost_pcc + total_training_support_fund + total_assessment_fee'));
    }

    protected function getTotalCostOfToolkits($allocationId): float
    {
        return Target::where('allocation_id' , $allocationId)
            ->sum(DB::raw('total_cost_of_toolkit_pcc'));
    }

    protected function getTotalAmount($allocationId): float
    {
        return Target::where('allocation_id' , $allocationId)
            ->sum(DB::raw('total_cost_of_toolkit_pcc'));
    }
}