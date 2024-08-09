<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Mask;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\QualificationTitleResource\Pages;
use App\Filament\Resources\QualificationTitleResource\RelationManagers;
use Filament\Actions\ImportAction;

class QualificationTitleResource extends Resource
{
    protected static ?string $model = QualificationTitle::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = "Qualification Titles";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Qualification Code')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('title')
                    ->label('Qualification Title')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('scholarship_program_id')
                    ->label('Scholarship Program')
                    ->relationship('scholarshipProgram', 'name')
                    ->required(),
                Select::make('sector_id')
                    ->label('Sector')
                    ->relationship('sector', 'name')
                    ->required(),
                TextInput::make('duration')
                    ->label('Duration')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('hrs'),
                TextInput::make('training_cost_pcc')
                    ->label('Training Cost PCC')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('cost_of_toolkit_pcc')
                    ->label('Cost of Toolkit PCC')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Qualification Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Qualification Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('scholarshipProgram.name')
                    ->label('Scholarship Program')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sector.name')
                    ->label('Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->suffix(' hrs'),
                TextColumn::make("training_cost_pcc")
                    ->label("Training Cost PCC")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("cost_of_toolkit_pcc")
                    ->label("Cost of Toolkit PCC")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ ')

            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn(StaticAction $action) => $action
                    ->button()
                    ->label('Filter')
            )
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('code')
                                        ->heading('Qualification Code'),
                                    Column::make('title')
                                        ->heading('Qualification Title'),
                                    Column::make('scholarshipProgram.name')
                                        ->heading('Scholarship Program'),
                                    Column::make('sector.name')
                                        ->heading('Sector'),
                                    Column::make('duration')
                                        ->heading('Duration'),
                                    Column::make('training_cost_pcc')
                                        ->heading('Training Cost PCC'),
                                    Column::make('cost_of_toolkit_pcc')
                                        ->heading('Cost of Toolkit PCC'),
                                    Column::make('created_at')
                                        ->heading('Date Created'),
                                ])->WithFilename(date('m-d-Y') . '- Qualification Titles'),


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
            'index' => Pages\ListQualificationTitles::route('/'),
            'create' => Pages\CreateQualificationTitle::route('/create'),
            'edit' => Pages\EditQualificationTitle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
