<?php

namespace App\Filament\Resources\AllocationResource\Widgets;

use App\Models\Allocation;
use App\Models\Target;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalAllocations = Allocation::sum('allocation');
        $totalAdminCost = Allocation::sum('admin_cost');
        $totalBalance = Allocation::sum('balance');
        $fundsUsedInTargets = Target::sum('total_amount');

        return [
            Stat::make('Allocations', '₱' . number_format($totalAllocations))
                ->description('Total allocation')
                ->color('info'),

            Stat::make('Admin Costs', '₱' . number_format($totalAdminCost))
                ->description('Total administrative cost')
                ->color('warning'),

            Stat::make('Funds Expended', '₱' . number_format($fundsUsedInTargets))
                ->description('Total funds used for targets')
                ->color('info'),

            Stat::make('Balance', '₱' . number_format($totalBalance ))
                ->description('Remaining balance')
                ->color('success'),
        ];
    }
}