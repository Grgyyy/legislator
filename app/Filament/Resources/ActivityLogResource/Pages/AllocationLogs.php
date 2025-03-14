<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\LegislativeTargetsResource;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\User;
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
        $allocation = Allocation::withTrashed()
            ->where('id', $allocationId)
            ->first();
        
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

        return Activity::query()
            ->where('subject_type', Allocation::class)
            ->where('subject_id', $allocationId);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([

                TextColumn::make('source_of_fund')
                    ->label('Source of Fund')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['soft_or_commitment']
                    ),

                TextColumn::make('attributor')
                    ->label('Attributor')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['attributor'] ?? '-'
                    ),

                TextColumn::make('attributor_particular')
                    ->label('Attributor Particular')
                    ->getStateUsing(function ($record) { 
                        $properties = json_decode($record->properties, true); 
                        $particulaId = $properties['attributor_particular'] ?? null;
                
                        if (!$particulaId) {
                            return '-';
                        }
                
                        $particular = Particular::find($particulaId);
                
                        if ($particular->subParticular->name === 'District') {
                            if ($particular->district->underMunicipality) {
                                return $particular ? "{$particular->subParticular->name} - {$particular->district->name}, {$particular->underMunicipality->name}, {$particular->district->province->name}" : '-';
                            }
                            else {
                                return $particular ? "{$particular->subParticular->name} - {$particular->district->name}, {$particular->district->province->name}" : '-';
                            }
                        }
                        elseif ($particular->subParticular->name === 'Senator' || $particular->subParticular->name === 'House Speaker' || $particular->subParticular->name === 'House Speaker (LAKAS)') {
                            return $particular ? "{$particular->subParticular->name}" : '-';
                        }
                        elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                            return $particular ? "{$particular->subParticular->name} - {$particular->district->province->region->name}" : '-';
                        }
                        elseif ($particular->subParticular->name === 'Party-list') {
                            return $particular ? "{$particular->subParticular->name} - {$particular->partylist->name}" : '-';
                        }
                        else {
                            return $particular ? "{$particular->subParticular->name}" : '-';
                        }

                    }),

                TextColumn::make('legislator')
                    ->label('Legislator')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['legislator']
                    ),

                TextColumn::make('particular')
                    ->label('Particular')
                    ->getStateUsing(function ($record) { 
                        $properties = json_decode($record->properties, true); 
                        $particulaId = $properties['particular'] ?? null;
                
                        if (!$particulaId) {
                            return '-';
                        }
                
                        $particular = Particular::find($particulaId);
                
                        if ($particular->subParticular->name === 'District') {
                            if ($particular->district->underMunicipality) {
                                return $particular ? "{$particular->subParticular->name} - {$particular->district->name}, {$particular->district->underMunicipality->name},  {$particular->district->province->name}" : '-';
                            }
                            else {
                                return $particular ? "{$particular->subParticular->name} - {$particular->district->name}, {$particular->district->province->name}" : '-';
                            }
                        }
                        elseif ($particular->subParticular->name === 'Senator' || $particular->subParticular->name === 'House Speaker' || $particular->subParticular->name === 'House Speaker (LAKAS)') {
                            return $particular ? "{$particular->subParticular->name}" : '-';
                        }
                        elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                            return $particular ? "{$particular->subParticular->name} - {$particular->district->province->region->name}" : '-';
                        }
                        elseif ($particular->subParticular->name === 'Party-list') {
                            return $particular ? "{$particular->subParticular->name} - {$particular->partylist->name}" : '-';
                        }
                        else {
                            return $particular ? "{$particular->subParticular->name}" : '-';
                        }

                    }),

                TextColumn::make('scholarship_program')
                    ->label('Scholarship Program')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['scholarship_program'] ?? '-'
                    ),

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
                    ->label('Allocation - Admin Cost')
                    ->prefix('₱')
                    ->getStateUsing(fn ($record) => 
                        number_format(
                            ((float) (optional(json_decode($record->properties, true))['allocation'] ?? 0)) 
                            - 
                            ((float) (optional(json_decode($record->properties, true))['admin_cost'] ?? 0)), 
                        2)
                    ),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->prefix('₱')
                    ->getStateUsing(fn ($record) => 
                        number_format((float) (optional(json_decode($record->properties, true))['balance'] ?? 0), 2)
                    ),
                
                TextColumn::make('year')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['year']
                    ),

                TextColumn::make('causer_id')
                    ->label("Processor's Name")
                    ->getStateUsing(function ($record) { 

                        $userId = $record->causer_id;
                        $user = User::find($userId);

                        return $user->name;
                    }),
                    
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                
            ])
            ->filters([
            ]);
    }
}

