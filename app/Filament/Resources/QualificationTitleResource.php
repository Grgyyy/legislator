<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use Filament\Actions\ImportAction;
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
use Filament\Tables\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Exports\QualificationTitleExporter;
use App\Filament\Resources\QualificationTitleResource\Pages;
use App\Filament\Resources\QualificationTitleResource\RelationManagers;

use function Laravel\Prompts\select;

class QualificationTitleResource extends Resource
{
    protected static ?string $model = QualificationTitle::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = "Qualification Titles";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Qualification Code')
                    ->required()
                    ->autocomplete(false)
                    ->unique(ignoreRecord: true),
                TextInput::make('title')
                    ->label('Qualification Title')
                    ->required()
                    ->autocomplete(false)
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
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('hrs'),
                TextInput::make('training_cost_pcc')
                    ->label('Training Cost PCC')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('cost_of_toolkit_pcc')
                    ->label('Cost of Toolkit PCC')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                Select::make('status_id')
                    ->relationship('status', 'desc')
                    // ->default('status', 'active')
                    ->hidden()
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
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('scholarshipProgram.name')
                    ->label('Scholarship Program')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sector.name')
                    ->label('Sector')
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
                EditAction::make()
                    ->hidden(fn($record) => $record->trashed()),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(QualificationTitleExporter::class)
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
