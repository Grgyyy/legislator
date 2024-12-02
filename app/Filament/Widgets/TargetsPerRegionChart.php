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
        // $data = Target::query()
        //     // Join necessary tables to access region data
        //     ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
        //     ->join('particulars', 'allocations.particular_id', '=', 'particulars.id')
        //     ->join('districts', 'particulars.district_id', '=', 'districts.id')
        //     ->join('provinces', 'districts.province_id', '=', 'provinces.id')
        //     ->join('regions', 'provinces.region_id', '=', 'regions.id')
        //     // Join targetStatus to get the status of the target
        //     ->join('target_statuses', 'targets.target_status_id', '=', 'target_statuses.id')
        //     // Group by region and target status to count targets by their status
        //     ->selectRaw('regions.name as region, target_statuses.desc as status, COUNT(targets.id) as count')
        //     ->whereIn('target_statuses.desc', ['Compliant', 'Non-Compliant', 'Pending'])
        //     ->groupBy('regions.name', 'target_statuses.desc')
        //     ->get();

        // $categories = $data->groupBy('region')->keys()->toArray();
        // $pendingData = $data->where('status', 'Pending')->pluck('count')->toArray();
        // $compliantData = $data->where('status', 'Compliant')->pluck('count')->toArray();
        // $nonCompliantData = $data->where('status', 'Non-Compliant')->pluck('count')->toArray();


        $categories = [
            'NCR',
            'Region 1',
            'Region 2',
            'Region 3',
            'Region 4',
            'Region 5',
            'Region 6',
            'Region 7',
            'Region 8',
            'Region 9',
            'Region 10',
            'Region 11',
            'CARAGA',
            'BARMM'
        ];

        $pendingData = [
            10,
            15,
            12,
            8,
            5,
            6,
            9,
            7,
            11,
            6,
            13,
            5,
            8,
            3
        ];

        $compliantData = [
            45,
            30,
            50,
            25,
            35,
            25,
            40,
            55,
            18,
            22,
            38,
            47,
            29,
            15
        ];

        $nonCompliantData = [
            5,
            5,
            2,
            12,
            10,
            10,
            6,
            5,
            7,
            12,
            6,
            8,
            3,
            2
        ];

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
