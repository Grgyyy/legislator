<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Models\SkillPriority;
use App\Models\Toolkit;
use App\Models\User;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;

class ToolkitLogs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ActivityLogResource::class;
    
    public function getTitle(): string
    {
        $toolkitId = request()->route('record');
        $toolkit = Toolkit::withTrashed()
            ->where('id', $toolkitId)
            ->first();
        
        return $toolkit->lot_name ?? 'Unknown Tookit';
    }

    public function getBreadcrumbs(): array
    {
        $toolkitId = request()->route('record');
        $toolkit = Toolkit::withTrashed()
            ->where('id', $toolkitId)
            ->first();

        return [
            route('filament.admin.resources.toolkits.index') => $toolkit ? $toolkit->lot_name : 'Toolkit',
            'Toolkit',
            'Logs'
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $toolkitId = request()->route('record');

        return Activity::query()
            ->where('subject_type', Toolkit::class)
            ->where('subject_id', $toolkitId)
            ->orderBy('created_at', 'desc');
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([

                TextColumn::make('qualification_title')
                    ->label('Qualification Title')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['qualification_title'] ?? '-'
                    ),

                TextColumn::make('available_number_of_toolkits')
                    ->label('Available No. of Toolkits')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['available_number_of_toolkits'] ?? '-'
                    ),

                TextColumn::make('number_of_toolkits')
                    ->label('No. of Toolkits')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['number_of_toolkits'] ?? '-'
                    ),

                TextColumn::make('price_per_toolkit')
                    ->label('Price per Toolkit')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['price_per_toolkit'] ?? '-'
                    ),

                TextColumn::make('total_abc_per_lot')
                    ->label('Total ABC per lot')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['total_abc_per_lot']
                    ),

                TextColumn::make('number_of_items_per_toolkit')
                    ->label('No. of Items per Toolkit')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['number_of_items_per_toolkit']
                    ),

                TextColumn::make('year')
                    ->label('Year')
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

                TextColumn::make('updated_at')
                    ->label('Date Encoded')
                    ->formatStateUsing(fn ($state) => 
                        \Carbon\Carbon::parse($state)->format('M j, Y') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . 
                        \Carbon\Carbon::parse($state)->format('h:i A')
                    )
                    ->html()
            ])
            ->filters([
            ]);
    }
}

