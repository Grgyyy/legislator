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
                'toolbar' => [
                    'show' => true,
                    'offsetX' => 0,
                    'offsetY' => 0,
                    'tools' => [
                        'download' => true,
                        // 'download' => '<img src="/static/icons/download.png" class="ico-download" width="20">'
                        'selection' => true,
                        'zoom' => true,
                        'zoomin' => true,
                        'zoomout' => true,
                        'pan' => true,
                        'reset' => true,
                        'customIcons' => [],
                    ],
                    'export' => [
                        'csv' => [
                            'filename' => 'Total Allocations per Scholarship Program',
                            'columnDelimiter' => ',',
                            'headerCategory' => 'Scholarship Program',
                            'headerValue' => 'Total Allocation',
                            'categoryFormatter' => "function(x) { return new Date(x).toDateString(); }",
                            'valueFormatter' => "function(y) { return y; }",
                        ],
                        'svg' => [
                            'filename' => 'Total Allocations per Scholarship Program',
                            'show' => false,
                        ],
                        'png' => [
                            'filename' => 'Total Allocations per Scholarship Program',
                        ],
                    ],
                ],
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
