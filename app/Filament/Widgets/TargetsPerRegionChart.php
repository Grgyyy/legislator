<?php

namespace App\Filament\Widgets;

use App\Models\Target;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TargetsPerRegionChart extends ApexChartWidget
{
    protected static ?string $chartId = 'TargetPerRegionChart';
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Targets';

    protected function getOptions(): array
    {
        $statuses = ['Pending', 'Compliant', 'Non-Compliant', 'Assigned'];

        // Fetch data grouped by region code, region name, and status
        $data = Target::query()
            ->join('allocations', 'targets.allocation_id', '=', 'allocations.id')
            ->join('particulars', 'allocations.particular_id', '=', 'particulars.id')
            ->join('districts', 'particulars.district_id', '=', 'districts.id')
            ->join('provinces', 'districts.province_id', '=', 'provinces.id')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            ->join('target_statuses', 'targets.target_status_id', '=', 'target_statuses.id')
            ->selectRaw('regions.name as region, regions.code as region_code, target_statuses.desc as status, COUNT(targets.id) as count')
            ->whereIn('target_statuses.desc', $statuses)
            ->groupBy('regions.name', 'regions.code', 'target_statuses.desc')
            ->get();

        // Extract unique regions with their codes and sort by region code
        $categories = $data->groupBy('region')->map(function ($item) {
            $regionCode = $item->first()->region_code;
            $regionName = $item->first()->region;
            return ['name' => "{$regionName} ({$regionCode})", 'code' => $regionCode];
        })
            ->values()
            ->sortBy('code')
            ->pluck('name')
            ->toArray();

        // Prepare series data
        $seriesData = [];
        foreach ($statuses as $status) {
            $statusData = $data->where('status', $status)->pluck('count')->toArray();

            // Only include the status if there's data for it
            if (count($statusData) > 0) {
                $seriesData[] = [
                    'name' => "{$status} Targets",
                    'data' => $statusData,
                ];
            }
        }

        // Define a valid filename for exports
        $exportFilename = 'Target_Per_Region_(Compliant_Pending_Non-Compliant)';

        return [
            'chart' => [
                'animations' => [
                    'enabled' => true,
                    'speed' => 800,
                    'animateGradually' => ['enabled' => true, 'delay' => 150],
                    'dynamicAnimation' => ['enabled' => true, 'speed' => 350],
                ],
                'type' => 'bar',
                'height' => 400,
                'width' => '100%',
            ],
            'toolbar' => [
                'show' => true,
                'tools' => [
                    'download' => true,
                    'selection' => true,
                    'zoom' => true,
                    'zoomin' => true,
                    'zoomout' => true,
                    'pan' => true,
                    'reset' => true,
                ],
                'export' => [
                    'csv' => [
                        'filename' => $exportFilename,
                        'columnDelimiter' => ',',
                        'headerCategory' => 'Region (Code)',
                        'headerValue' => 'Value',
                        'categoryFormatter' => "function(x) { return x; }",
                        'valueFormatter' => "function(y) { return y; }",
                    ],
                    'svg' => [
                        'filename' => $exportFilename,
                        'show' => false,
                    ],
                    'png' => [
                        'filename' => $exportFilename,
                    ],
                ],
            ],
            'series' => $seriesData,
            'xaxis' => [
                'categories' => $categories,
                'title' => ['style' => ['fontWeight' => 'bold', 'fontSize' => '10px']],
                'labels' => ['style' => ['fontSize' => '10px']],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontSize' => '10px']],
            ],
            'colors' => ['#feb144', '#9ee09e', '#ff6663', '#90e0ef'],
            'plotOptions' => ['bar' => ['borderRadius' => 3, 'horizontal' => false]],
            'legend' => [
                'show' => true,
                'position' => 'bottom',
                'horizontalAlign' => 'center',
                'labels' => ['useSeriesColors' => true],
            ],
        ];
    }
}
