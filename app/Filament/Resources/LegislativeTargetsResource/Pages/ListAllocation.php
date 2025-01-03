<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use App\Filament\Resources\LegislativeTargetsResource;
use App\Models\Allocation;
use App\Models\Legislator;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class ListAllocation extends ListRecords
{
    protected static string $resource = LegislativeTargetsResource::class;

    // Remove the static assignment of legislator name
    protected static ?string $title = null;

    // Dynamically set the title using the legislator's name
    protected function getLegislatorName(): string
    {
        $legislatorId = request()->route('record');
        $legislator = Legislator::find($legislatorId);

        return $legislator ? $legislator->name : 'Unknown Legislator';
    }

    public function mount(): void
    {
        // Set the title dynamically
        $legis = $this->getLegislatorName();
        static::$title = "{$legis}";
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Get legislator ID from the route
        $legislatorId = request()->route('record');

        if (!$legislatorId) {
            abort(404, 'Legislator ID not provided in the route.');
        }

        // Main query to fetch allocations, eager load related models like scholarship_program
        return Allocation::query()
            ->where('legislator_id', $legislatorId)
            ->with('scholarship_program'); // Eager load scholarship_program for optimized performance
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('scholarship_program.name')
                    ->label('Scholarship Program'),
                TextColumn::make('year')
                    ->label('Year'),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.legislative-targets.targetReport', ['record' => $record->id]),
            )
            ->filters([
                // Define your filters here if necessary
            ]);
    }
}

