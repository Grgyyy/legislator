<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use App\Models\Municipality;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MunicipalityResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Regions";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->required()
                    ->autocomplete(false),
                Select::make("province_id")
                    ->relationship("province", "name")
                    ->default(fn($get) => request()->get('province_id'))
                    ->reactive()
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No municipalities yet')
            ->columns([
                TextColumn::make("name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.region.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')
                                    ->heading('Legislator Name'),
                                Column::make('province.name')
                                    ->heading('Province'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Municipality')
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
            'index' => Pages\ListMunicipalities::route('/'),
            'create' => Pages\CreateMunicipality::route('/create'),
            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('province_id', (int) $routeParameter);
        }

        return $query;
    }
}
