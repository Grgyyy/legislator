<?php

namespace App\Filament\Widgets;

use App\Models\Target;
use App\Models\QualificationTitle;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TopTenQualificationChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'TopTenQualificationChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Top Ten Qualifications based on the Sum of Compliant';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // Fetch the data for top 10 qualifications and their compliant count
        $query = Target::query()
            ->join('target_statuses', 'targets.target_status_id', '=', 'target_statuses.id') // Join with target_statuses table
            ->join('qualification_titles', 'targets.qualification_title_id', '=', 'qualification_titles.id') // Join with qualification_titles table
            ->join('training_programs', 'qualification_titles.training_program_id', '=', 'training_programs.id') // Join with training_programs table
            ->where('target_statuses.desc', '=', 'Compliant') // Filter for 'Compliant' status
            ->whereNull('targets.deleted_at') // Exclude soft-deleted targets
            ->selectRaw('training_programs.title as qualification_title, SUM(targets.total_amount) AS compliant_count') // Select qualification title and compliant count
            ->groupBy('training_programs.title') // Group by qualification title
            ->orderByDesc('compliant_count') // Order by compliant count
            ->limit(10); // Limit to top 10 qualifications

        // Fetch the qualifications and their compliant counts
        $qualifications = $query->get();

        // Prepare the data for the chart
        $qualificationTitles = $qualifications->pluck('qualification_title')->toArray();
        $compliantCounts = $qualifications->pluck('compliant_count')->toArray();

        return [
            'chart' => [
                'type' => 'bar',
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
                'categories' => $qualificationTitles, // Use the qualification titles for the x-axis
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
                        'fontSize' => '8px', // Optional styling for y-axis labels
                    ],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => false, // Ensure bars are vertical
                ],
            ],
            'colors' => ['#f59e0b'], // Optional color customization
        ];
    }
}
