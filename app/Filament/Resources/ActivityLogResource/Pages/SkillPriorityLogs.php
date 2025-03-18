<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Models\SkillPriority;
use App\Models\User;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;

class SkillPriorityLogs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ActivityLogResource::class;
    
    public function getTitle(): string
    {
        $skillPrioId = request()->route('record');
        $skillPrio = SkillPriority::withTrashed()
            ->where('id', $skillPrioId)
            ->first();
        
        return $skillPrio->qualification_title ?? 'Unknown Skill Priority';
    }

    public function getBreadcrumbs(): array
    {
        $skillPrioId = request()->route('record');
        $skillPrio = SkillPriority::withTrashed()
            ->where('id', $skillPrioId)
            ->first();

        return [
            route('filament.admin.resources.skill-priorities.index') => $skillPrio ? $skillPrio->qualification_title : 'Qualification Title',
            'Skill Priority',
            'Logs'
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $skillPrioId = request()->route('record');

        return Activity::query()
            ->where('subject_type', SkillPriority::class)
            ->where('subject_id', $skillPrioId)
            ->orderBy('created_at', 'desc');
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([

                TextColumn::make('province')
                    ->label('Province')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['province']
                    ),

                TextColumn::make('district')
                    ->label('District')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['district'] ?? '-'
                    ),

                TextColumn::make('lot_name')
                    ->label('Lot Name')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['lot_name'] ?? '-'
                    ),

                TextColumn::make('qualification_title')
                    ->label('SOC Titles')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['qualification_title']
                    ),

                TextColumn::make('available_slots')
                    ->label('Available Slots')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['available_slots']
                    ),

                TextColumn::make('total_slots')
                    ->label('Total Slots')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['total_slots']
                    ),

                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => 
                        optional(json_decode($record->properties, true))['status']
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

