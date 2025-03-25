<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Widgets;
use App\Models\Allocation;
use App\Models\Target;
use App\Models\TargetStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class LegislativeTargetStatsOverview_ extends BaseWidget
{
    public ?int $legislatorId = null;
    public ?int $allocationId = null;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getCards(): array
    {
        $totalAllocation = $this->getTotalAllocation($this->legislatorId);
        $adminCost = $this->calculateAdminCost($totalAllocation);
        $totalAmount = $this->getTotalAmount($this->allocationId);
        $totalBalance = $totalAllocation - $adminCost - $totalAmount;

        Log::info('Legislator ID: ' . $this->legislatorId);

        return [
            Stat::make('Total Amount', '₱ ' . number_format($totalAmount))
                ->description('Total amount')
                ->color('warning'),
            Stat::make('Total Balance', '₱ ' . number_format($totalBalance))
                ->description('Total balance')
                ->color('warning'),
        ];
    }

    protected function getTotalAmount($allocationId): float
    {
        $compliantStatus = TargetStatus::where('desc', 'Compliant')->value('id');

        return Target::where('allocation_id', $allocationId)
            ->where('target_status_id', '=', $compliantStatus)
            ->sum('total_amount');
    }

    protected function calculateAdminCost($totalAllocation): float
    {
        return $totalAllocation * 0.02;
    }

    protected function getTotalAllocation($legislatorId): float
    {
        return Allocation::where('id', $legislatorId)
            ->sum('allocation');
    }
}

