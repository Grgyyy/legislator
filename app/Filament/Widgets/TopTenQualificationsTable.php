<?php

namespace App\Filament\Widgets;

use App\Models\Legislator;
use App\Models\QualificationTitle;
use App\Models\Tvi;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class TopTenQualificationsTable extends BaseWidget
{
    public function table(Table $table): Table
    {
        $query = QualificationTitle::query()
        ->join('targets', 'qualification_titles.id', '=', 'targets.qualification_title_id')
        ->join('target_statuses', 'targets.target_status_id', '=', 'target_statuses.id')
        ->join('training_programs', 'qualification_titles.training_program_id', '=', 'training_programs.id')
        ->join('scholarship_programs', 'qualification_titles.scholarship_program_id', '=', 'scholarship_programs.id')
        ->where('target_statuses.desc', '=', 'Compliant')
        ->selectRaw('qualification_titles.id, training_programs.title AS training_program_title, scholarship_programs.name AS scholarship_program_name, SUM(targets.total_amount) AS total_count')
        ->groupBy('qualification_titles.id', 'training_programs.title', 'scholarship_programs.name')
        ->orderByDesc('total_count')
        ->limit(10);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('training_program_title')
                    ->label('Training Program')
                    ->sortable(), 
                TextColumn::make('scholarship_program_name')
                    ->label('Scholarship Program')
                    ->sortable(), 
                TextColumn::make('total_count')
                    ->label('Total Amount')
                    ->sortable(),
            ]);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->id;
    }
}