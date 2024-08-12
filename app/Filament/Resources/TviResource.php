<?php

namespace App\Filament\Resources;

use App\Models\Tvi;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Navigation\NavigationGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TviResource\Pages;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TviResource\RelationManagers;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class TviResource extends Resource
{
    protected static ?string $model = Tvi::class;


    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'TVI';




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->required(),
                TextInput::make("district")
                    ->required(),
                Select::make("province_id")
                    ->relationship("province", "name")
                    ->required(),
                TextInput::make("municipality_class")
                    ->label("Municipality Class")
                    ->required(),
                Select::make('tvi_type')
                    ->label("Institution Type")
                    ->options([
                        'Private' => 'Private',
                        'Public' => 'Public'
                    ])
                    ->required(),
                Select::make('tvi_class')
                    ->label("Classification")
                    ->options([
                        'TVI' => 'TVI',
                        'HEI' => 'HEI',
                        'Farm School' => 'Farm School',
                        'TTI' => 'TTI',
                        'LGU-Run' => 'LGU-Run',
                        'SUC' => 'SUC',
                        'HUC' => 'HUC',
                        'NGO/NGA' => 'NGO/NGA',
                        'GOCC' => 'GOCC',
                    ])
                    ->required(),
                TextInput::make("address")
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("district")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.region.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("municipality_class")
                    ->label("Municipality Class")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("tvi_type")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("tvi_class")
                    ->label("Telephone Number")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("address")
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn(\Filament\Actions\StaticAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')->heading('TVI'),
                                Column::make('created_at')->heading('Date Created'),
                            ])
                            ->withFilename(date('Y-m-d') . ' - TVI')
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
            'index' => Pages\ListTvis::route('/'),
            'create' => Pages\CreateTvi::route('/create'),
            'edit' => Pages\EditTvi::route('/{record}/edit'),
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
