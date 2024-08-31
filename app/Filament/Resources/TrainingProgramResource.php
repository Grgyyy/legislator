<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingProgramResource\Pages;
use App\Models\TrainingProgram;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Form;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;

class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Qualification Titles";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Select::make('scholarshipPrograms')
                    ->label('Scholarship Programs')
                    ->multiple()
                    ->relationship('scholarshipPrograms', 'name')
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code'),
                TextColumn::make('title'),
                TextColumn::make('scholarshipPrograms.name')
                    ->label('Scholarship Programs')
                    ->formatStateUsing(fn($record) => $record->scholarshipPrograms->pluck('name')->implode(', '))
            ])
            ->filters([
                // Define any filters if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('code')
                                    ->heading('Qualification Code'),
                                Column::make('title')
                                    ->heading('Qualification Title'),
                                Column::make('formatted_scholarship_programs')
                                    ->heading('Scholarship Programs'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Training Programs')
                    ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingPrograms::route('/'),
            'create' => Pages\CreateTrainingProgram::route('/create'),
            'edit' => Pages\EditTrainingProgram::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    $routeParameter = request()->route('record');

    if ($routeParameter && is_numeric($routeParameter)) {
        $query->whereHas('scholarshipPrograms', function (Builder $query) use ($routeParameter) {
            $query->where('scholarship_programs.id', $routeParameter); // Disambiguate column name
        });
    }

    \Log::info('SQL Query:', ['query' => $query->toSql(), 'bindings' => $query->getBindings()]);

    return $query;
}

    
}
