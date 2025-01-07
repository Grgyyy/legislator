<?php

namespace App\Filament\Widgets;

use App\Models\Target;
use App\Models\Region;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TargetsPerRegionChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */

     protected int|string|array $columnSpan = 'full';
    protected static ?string $chartId = 'compliantTargetPerRegionChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Pending, Non- Compliant, and Compliant Targets Per Region';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // Fetch compliant, non-compliant, and pending targets per region
        $data = Target::query()
            // Join necessary tables to access region data
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->join('particulars', 'allocations.particular_id', '=', 'particulars.id')
            ->join('districts', 'particulars.district_id', '=', 'districts.id')
            ->join('provinces', 'districts.province_id', '=', 'provinces.id')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            // Join targetStatus to get the status of the target
            ->join('target_statuses', 'targets.target_status_id', '=', 'target_statuses.id')
            // Group by region and target status to count targets by their status
            ->selectRaw('regions.name as region, target_statuses.desc as status, SUM(targets.total_amount) as total_amount')
            ->whereIn('target_statuses.desc', ['Compliant', 'Non-Compliant', 'Pending'])
            ->groupBy('regions.name', 'target_statuses.desc')
            ->get();

        $categories = $data->groupBy('region')->keys()->toArray();
        $pendingData = $data->where('status', 'Pending')->pluck('total_amount')->toArray();
        $compliantData = $data->where('status', 'Compliant')->pluck('total_amount')->toArray();
        $nonCompliantData = $data->where('status', 'Non-Compliant')->pluck('total_amount')->toArray();

        return [
            'chart' => [
                'animations' => [
                    'enabled' => true,
                    'speed' => 800,
                    'animateGradually' => [
                        'enabled' => true,
                        'delay' => 150,
                    ],
                    'dynamicAnimation' => [
                        'enabled' => true,
                        'speed' => 350,
                    ],
                ],
                'type' => 'bar',
                'height' => 500,
                'width' => '100%',
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
                            'filename' => 'Target Per Region (Compliant, Pending, Non-Compliant)',
                            'columnDelimiter' => ',',
                            'headerCategory' => 'Region',
                            'headerValue' => 'value',
                            'categoryFormatter' => "function(x) { return new Date(x).toDateString(); }",
                            'valueFormatter' => "function(y) { return y; }",
                        ],
                        'svg' => [
                            'filename' => 'Target Per Region (Compliant, Pending, Non-Compliant)',
                            'show' => false,
                        ],
                        'png' => [
                            'filename' => 'Target Per Region (Compliant, Pending, Non-Compliant)',
                        ],
                    ],
                    'autoSelected' => 'zoom',
                ],
                'zoom' => [
                    'enabled' => true,
                    'type' => 'x',
                    'autoScaleYaxis' => false,
                    'zoomedArea' => [
                        'fill' => [
                            'color' => '#90CAF9',
                            'opacity' => 0.4,
                        ],
                        'stroke' => [
                            'color' => '#0D47A1',
                            'opacity' => 0.4,
                            'width' => 1,
                        ],
                    ],
                ],
                'panning' => [
                    'enabled' => true,
                    'type' => 'x',
                    'panBarWidth' => 8,
                    'flicker' => 50,
                ],
            ],
            'series' => [
                [
                    'name' => 'Pending Targets',
                    'data' => $pendingData,
                ],
                [
                    'name' => 'Compliant Targets',
                    'data' => $compliantData,
                ],
                [
                    'name' => 'Non-Compliant Targets',
                    'data' => $nonCompliantData,
                ],
            ],
            'xaxis' => [
                'categories' => $categories,
                'title' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontWeight' => 'bold',
                        'fontSize' => '10px',
                    ],
                ],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontSize' => '10px',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontSize' => '10px',
                    ],
                ],
            ],
            'colors' => ['#2196F3', '#4CAF50', '#F44336'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => false,
                ],
            ],
            'legend' => [
                'show' => true,
                'position' => 'bottom',
                'horizontalAlign' => 'center',
                'labels' => [
                    'useSeriesColors' => true,
                ],
            ],
        ];
    }
}
