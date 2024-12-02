<?php

namespace App\Filament\Widgets;

use App\Models\Target;
use App\Models\Region;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CompliantTargetPerRegionChart extends ApexChartWidget
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
    protected static ?string $heading = 'Compliant Targets Per Region';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    // protected function getOptions(): array
    // {
    //     // Fetch compliant targets per region
    //     $data = Target::query()
    //         ->whereHas('targetStatus', function ($query) {
    //             $query->where('desc', 'Compliant');
    //         })
    //         ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
    //         ->join('particulars', 'allocations.particular_id', '=', 'particulars.id')
    //         ->join('districts', 'particulars.district_id', '=', 'districts.id')
    //         ->join('provinces', 'districts.province_id', '=', 'provinces.id')
    //         ->join('regions', 'provinces.region_id', '=', 'regions.id')
    //         ->selectRaw('regions.name as region, COUNT(targets.id) as count')
    //         ->groupBy('regions.name')
    //         ->get();

    //     $categories = $data->pluck('region')->toArray();
    //     $seriesData = $data->pluck('count')->toArray();

    //     // return [
    //     //     'chart' => [
    //     //         'type' => 'bar',
    //     //         'height' => 300,
    //     //     ],
    //     //     'series' => [
    //     //         [
    //     //             'name' => 'Compliant Targets',
    //     //             'data' => $seriesData,
    //     //         ],
    //     //     ],
    //     //     'xaxis' => [
    //     //         'categories' => $categories,
    //     //         'labels' => [
    //     //             'style' => [
    //     //                 'fontFamily' => 'inherit',
    //     //             ],
    //     //         ],
    //     //     ],
    //     //     'yaxis' => [
    //     //         'labels' => [
    //     //             'style' => [
    //     //                 'fontFamily' => 'inherit',
    //     //             ],
    //     //         ],
    //     //     ],
    //     //     'colors' => ['#4CAF50'], // Green color for compliant
    //     //     'plotOptions' => [
    //     //         'bar' => [
    //     //             'borderRadius' => 3,
    //     //             'horizontal' => false,
    //     //         ],
    //     //     ],
    //     // ];

    //     return [
    //         'chart' => [
    //             'type' => 'bar',
    //             'height' => 300,
    //         ],
    //         'series' => [
    //             [
    //                 'name' => 'Compliant Targets',
    //                 'data' => $seriesData,
    //             ],
    //         ],
    //         'xaxis' => [
    //             'categories' => $categories,
    //             'title' => [
    //                 'text' => 'Name of Region',
    //                 'style' => [
    //                     'fontFamily' => 'inherit',
    //                     'fontWeight' => 'bold',
    //                     'fontSize' => '14px',
    //                 ],
    //             ],
    //             'labels' => [
    //                 'style' => [
    //                     'fontFamily' => 'inherit',
    //                     'fontSize' => '12px',
    //                 ],
    //             ],
    //         ],
    //         'yaxis' => [
    //             'labels' => [
    //                 'style' => [
    //                     'fontFamily' => 'inherit',
    //                 ],
    //             ],
    //         ],
    //         'colors' => ['#4CAF50'],
    //         'plotOptions' => [
    //             'bar' => [
    //                 'borderRadius' => 3,
    //                 'horizontal' => false,
    //             ],
    //         ],
    //     ];

    // }

    protected function getOptions(): array
    {
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
                'height' => 300,
                'width' => '100%',
                'toolbar' => [
                    'show' => true,
                    'offsetX' => 0,
                    'offsetY' => 0,
                    'tools' => [
                        'download' => true,
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
                        'fontSize' => '14px',
                    ],
                ],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontSize' => '12px',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
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
