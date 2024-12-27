<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use App\Filament\Resources\LegislativeTargetsResource;
use App\Models\Allocation;
use App\Models\Target;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;

class TargetReport extends ListRecords
{
    protected static string $resource = LegislativeTargetsResource::class;

    protected static ?string $title = null;

    protected function getLegislatorName(): string
    {
        $allocationId = request()->route('record');
        $allocation = Allocation::find($allocationId);

        return $allocation ? $allocation->legislator->name : 'Unknown Legislator';
    }

    public function mount(): void
    {
        $legis = $this->getLegislatorName();
        static::$title = "{$legis}";
    }

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
                $query->where('allocation_id', $allocationId)
                    ->orWhere('attribution_allocation_id', $allocationId);
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
