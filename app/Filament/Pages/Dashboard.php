<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TargetsPerRegionChart;  // Import the TargetsPerRegionChart widget
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function getWidgets(): array
    {
        return [
            TargetsPerRegionChart::class,
        ];
    }
}
