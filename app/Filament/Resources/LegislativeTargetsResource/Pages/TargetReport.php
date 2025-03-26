<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use App\Exports\TargetReportExport;
use App\Filament\Resources\LegislativeTargetsResource;
use App\Filament\Resources\LegislativeTargetsResource\Widgets\LegislativeTargetStatsOverview;
use App\Filament\Resources\LegislativeTargetsResource\Widgets\LegislativeTargetStatsOverview_;
use App\Models\Allocation;
use App\Models\Target;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Maatwebsite\Excel\Facades\Excel;

class TargetReport extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = LegislativeTargetsResource::class;

    protected static ?string $title = null;

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

    public $allocationId;

    public function mount(): void
    {
        $this->allocationId = request()->route('record');

        $allocation = Allocation::find($this->allocationId);
        if (!$allocation) {
            abort(404, 'Allocation not found.');
        }
        $legis = $this->getLegislatorName();
        $particular = $this->getParticularName();
        static::$title = "{$legis} - {$particular}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('TargetReportExport')
                ->label('Export')
                ->icon('heroicon-o-document-arrow-down')
                ->url(route('export.targets', ['allocationId' => $this->allocationId]))
                ->action(function () {
                    try {
                        return Excel::download(new TargetReportExport($this->allocationId), now()->format('m-d-Y') . ' - ' . 'Target Report.xlsx');
                    } catch (\Throwable $e) {
                        NotificationHandler::sendErrorNotification('Export Failed', $e->getMessage());
                    }
                }),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        $allocationId = request()->route('record');

        return Target::query()
            ->select([
                'targets.*',
                'tvis.name as tvi_name',
                'districts.name as district_name',
                'provinces.name as province_name',
                'regions.name as region_name',
                'municipalities.name as municipality_name',
                'training_programs.title as training_program_title',
                'target_statuses.desc as target_status'
            ])
            ->join('tvis', 'tvis.id', '=', 'targets.tvi_id')
            ->join('districts', 'districts.id', '=', 'tvis.district_id')
            ->join('provinces', 'provinces.id', '=', 'districts.province_id')
            ->join('regions', 'regions.id', '=', 'provinces.region_id')
            ->join('municipalities', 'municipalities.id', '=', 'districts.municipality_id')
            ->join('qualification_titles', 'qualification_titles.id', '=', 'targets.qualification_title_id')
            ->join('training_programs', 'training_programs.id', '=', 'qualification_titles.training_program_id')
            ->join('target_statuses', 'target_statuses.id', '=', 'targets.target_status_id')
            ->where('targets.allocation_id', $allocationId);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('region_name')
                    ->label('Region Name'),
                TextColumn::make('province_name')
                    ->label('Province'),
                TextColumn::make('municipality_name')
                    ->label('Municipality'),
                TextColumn::make('district_name')
                    ->label('Municipality'),
                TextColumn::make('tvi_name')
                    ->label('Institution'),
                TextColumn::make('training_program_title')
                    ->label('Qualification Title'),
                TextColumn::make('number_of_slots')
                    ->label('No. of Slots'),
                TextColumn::make('training_cost')
                    ->label('Training Cost PCC (TC, AF, TSF, EF)')
                    ->getStateUsing(function ($record) {
                        $trainingCost = ($record->total_training_cost_pcc + $record->total_assessment_fee + $record->total_training_support_fund + $record->total_entrepreneurship_fee) / $record->number_of_slots;

                        return '₱ ' . number_format($trainingCost, 2);
                    }),

                TextColumn::make('cost_of_toolkits')
                    ->label('Cost of Toolkits PCC')
                    ->getStateUsing(function ($record) {
                        $oostOfToolkit = $record->total_cost_of_toolkit_pcc / $record->number_of_slots;
                        return '₱ ' . number_format($oostOfToolkit, 2);
                    }),

                TextColumn::make('total_training_cost')
                    ->label('Total Training Cost (TC, AF, TSF, EF)')
                    ->getStateUsing(function ($record) {
                        $trainingCost = ($record->total_training_cost_pcc + $record->total_assessment_fee + $record->total_training_support_fund + $record->total_entrepreneurship_fee);

                        return '₱ ' . number_format($trainingCost, 2);
                    }),

                TextColumn::make('total_cost_of_toolkits')
                    ->label('Total Cost of Toolkit')
                    ->getStateUsing(function ($record) {
                        $oostOfToolkit = $record->total_cost_of_toolkit_pcc;
                        return '₱ ' . number_format($oostOfToolkit, 2);
                    }),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->getStateUsing(function ($record) {
                        return '₱ ' . number_format($record->total_amount, 2);
                    }),

                TextColumn::make('target_status')
                    ->label('Status'),
            ])
            ->filters([]);
    }

    public function getTableRecordKey($record): string
    {
        return $record->institution_name . '-' . $record->total_slots;
    }
}
