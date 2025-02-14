<?php

namespace App\Filament\Resources\LegislativeTargetsResource\Pages;

use App\Exports\PendingTargetExport;
use App\Filament\Resources\LegislativeTargetsResource;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception;

class ListAllocation extends ListRecords
{
    protected static string $resource = LegislativeTargetsResource::class;

    protected static ?string $title = null;

    protected function getLegislatorName(): string
    {
        $legislatorId = request()->route('record');
        $legislator = Legislator::find($legislatorId);

        return $legislator ? $legislator->name : 'Unknown Legislator';
    }

    public function mount(): void
    {
        $legis = $this->getLegislatorName();
        static::$title = "{$legis}";
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $legislatorId = request()->route('record');

        if (!$legislatorId) {
            abort(404, 'Legislator ID not provided in the route.');
        }

        return Allocation::query()
            ->where('legislator_id', $legislatorId)
            ->has('target')
            ->with('scholarship_program');
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
            ]);
    }
}

