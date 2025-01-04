<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectProposalResource\Pages;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectProposalResource extends Resource
{
    protected static ?string $model = QualificationTitle::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?string $navigationLabel = "Project Proposal Programs";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('program_name')
                    ->label('Project Proposal Program')
                    ->required()
                    ->markAsRequired(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trainingProgram.title')
                    ->label('Program Name')
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->sortable(),

                TextColumn::make('scholarshipProgram.name')
                    ->label('Scholarship Programs')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->scholarshipProgram->pluck('name')->implode(', ');
                    }),
            ])
            ->filters([/* Any filters you want to apply */])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['trainingProgram', 'scholarshipProgram'])
            ->where('qualification_titles.soc', 0)
            ->select('qualification_titles.*')
            ->groupBy('qualification_titles.training_program_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectProposals::route('/'),
            'create' => Pages\CreateProjectProposal::route('/create'),
            'edit' => Pages\EditProjectProposal::route('/{record}/edit'),
        ];
    }
}
