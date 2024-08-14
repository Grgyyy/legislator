<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProvinceResource\Pages;
use App\Models\Province;
use App\Models\District;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationParentItem = "Regions";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->autocomplete(false),
                Forms\Components\Select::make('region_id')
                    ->label('Region')
                    ->relationship('region', 'name')
                    ->default(fn($get) => request()->get('region_id')) // Set the default value from the URL query parameter
                    ->reactive()
                    ->required()
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->url(fn ($record) => route('filament.admin.resources.provinces.showMunicipalities', ['record' => $record->id])),
                Tables\Columns\TextColumn::make('region.name')
                    ->sortable()
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
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->trashed()),
                Tables\Actions\DeleteAction::make()
                    ->action(function ($record) {
                        $record->delete();
                        return redirect()->route('filament.admin.resources.provinces.index'); 
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()->withColumns([
                            Column::make('name')->heading('Name'),
                            Column::make('region.name')->heading('Region Name'),
                            Column::make('created_at')->heading('Date Created'),
                        ])->withFilename(date('m-d-Y') . ' - Provinces'),
                    ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define any relations if necessary
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProvinces::route('/'),
            'create' => Pages\CreateProvince::route('/create'),
            'edit' => Pages\EditProvince::route('/{record}/edit'),
            'showMunicipalities' => Pages\ShowMunicipalities::route('/{record}/municipalities'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        // Check if we're on the edit page by checking for the presence of 'record' in the route
        $routeParameter = request()->route('record');

        // If it's not an edit page or the 'record' parameter is not numeric, apply the filter
        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('region_id', (int) $routeParameter);
        }

        return $query;
    }

}
