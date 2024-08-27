<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\QualificationTitleResource\Pages;
use Filament\Resources\Pages\CreateRecord;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;

class QualificationTitleResource extends Resource
{
    protected static ?string $model = QualificationTitle::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?string $navigationLabel = "Qualification Titles";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->required()
                    ->autocomplete(false)
                    ->unique(ignoreRecord: true),
                TextInput::make('title')
                    ->label('Qualification Title')
                    ->required()
                    ->autocomplete(false)
                    ->unique(ignoreRecord: true),
<<<<<<< HEAD
                Select::make('scholarship_program_id')
                    ->label('Scholarship Program')
                    ->relationship('scholarshipProgram', 'name')
                    ->required(),
=======
                // Many-to-many relationship field
                Select::make('scholarshipPrograms')
                    ->label('Scholarship Programs')
                    ->multiple()
                    ->relationship('scholarshipPrograms', 'name')
                    ->preload()
                    ->searchable(),
>>>>>>> d0f515d (fix: Qualification Title Creation and Table Rendering)
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
                    ->label('Status')
                    ->default(1)
                    ->relationship('status', 'desc')
<<<<<<< HEAD
                    ->hidden(fn (Page $livewire) => $livewire instanceof CreateRecord),     
=======
>>>>>>> d0f515d (fix: Qualification Title Creation and Table Rendering)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No qualification titles yet')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Qualification Title')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('scholarshipPrograms.name')
                    ->label('Scholarship Programs')
                    ->formatStateUsing(fn($record) => $record->scholarshipPrograms->pluck('name')->implode(', '))
                    ,
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
                    ->prefix('₱ '),
                TextColumn::make("status.desc")
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('code')
                                    ->heading('Qualification Code'),
                                Column::make('title')
                                    ->heading('Qualification Title'),
                                Column::make('scholarshipPrograms.name')
                                    ->heading('Scholarship Program'),
                                Column::make('duration')
                                    ->heading('Duration'),
                                Column::make('training_cost_pcc')
                                    ->heading('Training Cost PCC'),
                                Column::make('cost_of_toolkit_pcc')
                                    ->heading('Cost of Toolkit PCC'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Qualification Title')
                    ]),
                ]),
            ]);
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
