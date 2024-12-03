<?php

namespace App\Filament\Widgets;

use App\Models\Tvi;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopTenInstitutionChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'topTenInstitutionChart';

    // protected int|string|array $columnSpan = 'full';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Top Ten Institutions based on the Sum of Compliant';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {

        $query = Tvi::query()
            ->join('targets', 'tvis.id', '=', 'targets.tvi_id') // Join with targets table
            ->join('target_statuses', 'targets.target_status_id', '=', 'target_statuses.id') // Join with target_statuses table
            ->where('target_statuses.desc', '=', 'Compliant') // Filter for 'Compliant' status
            ->whereNull('targets.deleted_at') // Exclude soft-deleted targets
            ->selectRaw('tvis.name as institution_name, SUM(targets.total_amount) AS compliant_count') // Select institution name and compliant count
            ->groupBy('tvis.name') // Group by institution name
            ->orderByDesc('compliant_count')
            ->limit(10);

        // Fetch the institutions and their compliant counts
        $institutions = $query->get();

        // Prepare the data for the chart
        $institutionsNames = $institutions->pluck('institution_name')->toArray();
        $compliantCounts = $institutions->pluck('compliant_count')->toArray();

        return [
            'chart' => [
                'type' => 'bar', // Specify bar chart
                'height' => 300,
                'toolbar' => ['show' => true],

            ],
            'series' => [
                [
                    'name' => 'Total Amount',
                    'data' => $compliantCounts, // Use the compliant count data
                ],
            ],
            'xaxis' => [
                'categories' => $institutionsNames, // Use the institution names for the x-axis
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',

                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'fontSize' => '8px',
                    ],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => false,
                ],
            ],
            'colors' => ['#f59e0b'], // Optional color customization
        ];
    }
}
