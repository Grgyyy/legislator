<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\LegislativeTargetsResource;
use App\Models\Allocation;
use App\Models\Legislator;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;

class AllocationLogs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = LegislativeTargetsResource::class;

    protected function getLegislatorName(): string
    {
        $allocationId = request()->route('record');
        $allocation = Allocation::find($allocationId);

        if (!$allocation) {
            abort(404, 'Allocation not found.');
        }

        return $allocation->legislator->name ?? 'Unknown Legislator';
    }

    
    public function getTitle(): string
    {
        $allocationId = request()->route('record');
        $allocation = Allocation::find($allocationId);

        if (!$allocation) {
            return 'Allocation Not Found';
        }

        return $allocation->legislator->name ?? 'Unknown Legislator';
    }

    public function getBreadcrumbs(): array
    {
        $allocationId = request()->route('record');
        $allocation = Allocation::find($allocationId);

        return [
            route('filament.admin.resources.allocations.index') => $allocation ? $allocation->legislator->name : 'Legislator',
            'Allocation',
            'Logs'
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $allocationId = request()->route('record');

        return Activity::where('subject_type', Allocation::class)
                    ->where('subject_id', $allocationId);
    }


    // protected function getLegislatorName(): string
    // {
    //     $legislatorId = request()->route('record');
    //     $legislator = Legislator::find($legislatorId);

    //     return $legislator ? $legislator->name : 'Unknown Legislator';
    // }

    // public function mount(): void
    // {
    //     $legis = $this->getLegislatorName();
    //     static::$title = "{$legis}";
    // }

    // protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    // {
    //     $legislatorId = request()->route('record');

    //     if (!$legislatorId) {
    //         abort(404, 'Legislator ID not provided in the route.');
    //     }

    //     return Allocation::query()
    //         ->where('legislator_id', $legislatorId)
    //         ->has('target')
    //         ->with('scholarship_program');
    // }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('legislator')
                    ->label('Legislator')
                    ->getStateUsing(function ($record) { // Use function() {} instead of fn()
                        $properties = json_decode($record->properties, true); // Decode JSON safely
                        $legislatorId = $properties['legislator_id'] ?? null;
                
                        if (!$legislatorId) {
                            return 'Unknown Legislator'; // Handle missing legislator_id
                        }
                
                        $legislator = Legislator::find($legislatorId);
                
                        return $legislator ? $legislator->name : 'Unknown Legislator';
                    }),

                TextColumn::make('allocation')
                    ->label('Allocation')
                    ->prefix('₱')
                    ->getStateUsing(fn ($record) => 
                        number_format((float) (optional(json_decode($record->properties, true))['allocation'] ?? 0), 2)
                    ),
                
                TextColumn::make('adminCost')
                    ->label('Admin Cost')
                    ->prefix('₱')
                    ->getStateUsing(fn ($record) => 
                        number_format((float) (optional(json_decode($record->properties, true))['admin_cost'] ?? 0), 2)
                    ),

                TextColumn::make('allocation_without_adminCost')
                    ->label('Allocation w/o Admin Cost')
                    ->prefix('₱')
                    ->getStateUsing(fn ($record) => 
                        number_format(
                            ((float) (optional(json_decode($record->properties, true))['allocation'] ?? 0)) 
                            - 
                            ((float) (optional(json_decode($record->properties, true))['admin_cost'] ?? 0)), 
                        2)
                    ),

                TextColumn::make('balance')
                    ->label('Balance'),
                
                TextColumn::make('year')
                    ->label('Year')
                    
                
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.legislative-targets.targetReport', ['record' => $record->id]),
            )
            ->filters([
            ]);
    }
}

