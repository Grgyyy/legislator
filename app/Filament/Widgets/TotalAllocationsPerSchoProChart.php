<?php

namespace App\Filament\Widgets;

use App\Models\Allocation;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TotalAllocationsPerSchoProChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'totalAllocationsPerSchoProChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Total Allocations';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $data = Allocation::with('scholarship_program')
            ->selectRaw('scholarship_program_id, SUM(allocation) as total_allocation')
            ->groupBy('scholarship_program_id')
            ->get();

        $labels = $data->map(fn($item) => $item->scholarship_program->name ?? 'Unknown Program')->toArray();
        $series = $data->pluck('total_allocation')->map(fn($value) => (int) $value)->toArray();

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 300,
            ],
            'series' => $series,
            'labels' => $labels,
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
        ];
    }
}
