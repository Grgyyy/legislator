<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use Filament\Tables;
use App\Models\Target;
use App\Models\Allocation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\DB;
use App\Exports\TargetReportExport;
use App\Services\NotificationHandler;
use Filament\Tables\Columns\TextColumn;
use PhpOffice\PhpSpreadsheet\Exception;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Filament\Resources\AllocationResource\Widgets\StatsOverview;
use App\Filament\Resources\LegislativeTargetsResource;
use App\Filament\Resources\LegislativeTargetsResource\Widgets\LegislativeTargetStatsOverview;
use App\Filament\Resources\LegislativeTargetsResource\Widgets\LegislativeTargetStatsOverview_;
use App\Filament\Resources\LegislativeTargetsResource\Widgets\StatsOverview as WidgetsStatsOverview;

class TargetReport extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = LegislativeTargetsResource::class;

    protected static ?string $title = null;

    /**
     * Retrieves legislator name based on allocation ID in the route.
     */
    protected function getLegislatorName(): string
    {
        $allocationId = request()->route('record');
        $allocation = Allocation::find($allocationId);

        if (!$allocation) {
            abort(404, 'Allocation not found.');
        }

        return $allocation->legislator->name ?? 'Unknown Legislator';
    }

    protected function getHeaderWidgets(): array
    {
        $legislatorId = request()->route('record');
        $scholarshipProgramId = request()->route('record');
        $allocationId = request()->route('record');

        return [
            LegislativeTargetStatsOverview::make([
                'legislatorId' => $legislatorId,
                'scholarshipProgramId' => $scholarshipProgramId,
                'allocationId' => $allocationId,
            ]),
            LegislativeTargetStatsOverview_::make([
                'legislatorId' => $legislatorId,
                'allocationId' => $allocationId,
            ]),
        ];
    }

    protected function getParticularName(): string
    {
        $allocationId = request()->route('record');
        $allocation = Allocation::find($allocationId);

        if (!$allocation) {
            abort(404, 'Allocation not found.');
        }

        $subParticularName = $allocation->particular->subParticular->name;


        if (in_array($subParticularName, ['RO Regular', 'CO Regular'])) {
            return $subParticularName . ' ' . $allocation->particular->region->name;
        } elseif ($subParticularName === 'District') {
            if ($allocation->particular->district->province->region->name === 'NCR') {
                return $allocation->particular->district->name . ', ' . $allocation->particular->district->underMunicipality->name;
            } else {
                return $allocation->particular->district->name . ', ' . $allocation->particular->district->province->name;
            }
        } else {
            return $subParticularName;
        }
    }

    /**
     * Mount method to set the title dynamically.
     */
    public $allocationId;

    public function mount(): void
    {
        // Fetch allocationId from the route
        $this->allocationId = request()->route('record');

        // Ensure that the allocationId is valid and exists
        $allocation = Allocation::find($this->allocationId);
        if (!$allocation) {
            abort(404, 'Allocation not found.');
        }

        // Set the title dynamically
        $legis = $this->getLegislatorName();
        $particular = $this->getParticularName();
        static::$title = "{$legis} - {$particular}";
    }

    /**
     * Define header widgets.
    //  */
    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         StatsOverview::class,
    //     ];
    // }

    /**
     * Define header actions, including the export action.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('TargetReportExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->url(route('export.targets', ['allocationId' => $this->allocationId]))
                ->action(function () {
                    try {
                        return Excel::download(new TargetReportExport($this->allocationId), 'pending_target_export.xlsx');
                    } catch (\Throwable $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', $e->getMessage());
                    }
                }),
        ];
    }


    /**
     * Define the table query.
     */
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $allocationId = request()->route('record');

        if (!$allocationId) {
            abort(404, 'Allocation ID not provided in the route.');
        }

        return Target::query()
            ->join('tvis', 'tvis.id', '=', 'targets.tvi_id')
            ->join('districts', 'districts.id', '=', 'tvis.district_id')
            ->join('provinces', 'provinces.id', '=', 'districts.province_id')
            ->join('regions', 'regions.id', '=', 'provinces.region_id')
            ->join('municipalities', 'municipalities.id', '=', 'districts.municipality_id')
            ->join('qualification_titles', 'qualification_titles.id', '=', 'targets.qualification_title_id')
            ->join('training_programs', 'training_programs.id', '=', 'qualification_titles.training_program_id')
            ->where(function ($query) use ($allocationId) {
                $query->where('allocation_id', $allocationId);
                // ->orWhere('attribution_allocation_id', $allocationId);
            })
            ->select(
                'regions.name as region',
                'provinces.name as province',
                'municipalities.name as municipality',
                'tvis.name as institution_name',
                'training_programs.title as qualification_title',

                DB::raw('SUM(targets.number_of_slots) as total_slots'),
                DB::raw('SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) / SUM(targets.number_of_slots) as training_cost'),
                DB::raw('SUM(targets.total_cost_of_toolkit_pcc) / SUM(targets.number_of_slots) as cost_of_toolkits'),
                DB::raw('SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee) as total_training_cost'),
                DB::raw('SUM(targets.total_cost_of_toolkit_pcc) as total_cost_of_toolkits'),
                DB::raw('SUM(targets.total_training_cost_pcc + targets.total_assessment_fee + targets.total_training_support_fund + targets.total_entrepreneurship_fee + targets.total_cost_of_toolkit_pcc) as total_amount')
            )
            ->groupBy('regions.name', 'provinces.name', 'municipalities.name', 'tvis.name', 'training_programs.title');
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('region')
                    ->label('Region'),
                TextColumn::make('province')
                    ->label('Province'),
                TextColumn::make('municipality')
                    ->label('Municipality'),
                TextColumn::make('institution_name')
                    ->label('Institution'),
                TextColumn::make('qualification_title')
                    ->label('Qualification Title'),
                TextColumn::make('total_slots')
                    ->label('Total No. of Slots'),
                TextColumn::make('training_cost')
                    ->label('Training Cost PCC (TC, AF, TSF, EF)')
                    ->getStateUsing(function ($record) {
                        return '₱ ' . number_format($record->training_cost, 2);
                    }),
                TextColumn::make('cost_of_toolkits')
                    ->label('Cost of Toolkits PCC')
                    ->getStateUsing(function ($record) {
                        return '₱ ' . number_format($record->cost_of_toolkits, 2);
                    }),
                TextColumn::make('total_training_cost')
                    ->label('Total Training Cost (TC, AF, TSF, EF)')
                    ->getStateUsing(function ($record) {
                        return '₱ ' . number_format($record->total_training_cost, 2);
                    }),
                TextColumn::make('total_cost_of_toolkits')
                    ->label('Total Cost of Toolkits')
                    ->getStateUsing(function ($record) {
                        return '₱ ' . number_format($record->total_cost_of_toolkits, 2);
                    }),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->getStateUsing(function ($record) {
                        return '₱ ' . number_format($record->total_amount, 2);
                    }),
            ])
            ->filters([]);
    }

    public function getTableRecordKey($record): string
    {
        return $record->institution_name . '-' . $record->total_slots;
    }
}
