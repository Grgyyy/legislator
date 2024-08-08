<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TviResource\Pages;
use App\Filament\Resources\TviResource\RelationManagers;
use App\Models\Tvi;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;


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
                    ->searchable(),
                TextColumn::make("district")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("province.name")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("province.region.name")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("municipality_class")
                    ->label("Municipality Class")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("tvi_type")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("tvi_class")
                    ->label("Telephone Number")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("address")
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn (\Filament\Actions\StaticAction $action) => $action
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
