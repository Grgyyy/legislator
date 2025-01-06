<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Widgets;

use App\Models\Allocation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalAllocations = Allocation::sum('allocation');
        $totalAdminCost = Allocation::sum('admin_cost');

        return [
            Stat::make('Total Allocations', '₱' . number_format($totalAllocations, 2, '.', ','))
                ->description('Total allocated funds')
                ->color('success'),

            Stat::make('Total Admin Cost', '₱' . number_format($totalAdminCost, 2, '.', ','))
                ->description('Total admin costs')
                ->color('warning')
        ];
    }
}
