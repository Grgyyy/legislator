<?php

namespace App\Filament\Resources\AllocationResource\Widgets;

use App\Models\Allocation;
use App\Models\Target;
use App\Models\TargetStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $nonCompliantStatus = TargetStatus::where('desc', 'Non-Compliant')
            ->first();
        $totalAllocations = Allocation::sum('allocation');
        $totalAdminCost = Allocation::sum('admin_cost');
        $totalBalance = Allocation::sum('balance');
        $fundsUsedInTargets = Target::whereNot('target_status_id', $nonCompliantStatus->id)
            ->sum('total_amount');

        return [
            Stat::make('Total Allocations', '₱' . number_format($totalAllocations))
                ->description('Admin Cost: ₱ ' . number_format($totalAdminCost))
                ->color('info'),

            Stat::make('Total Funds Expended', '₱' . number_format($fundsUsedInTargets))
                ->description('Total funds used for targets')
                ->color('info'),

            Stat::make('Total Balance', '₱' . number_format($totalBalance))
                ->description('Remaining balance')
                ->color('success'),
        ];
    }
}
