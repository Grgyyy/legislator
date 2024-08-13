<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistrictResource\Pages;
use App\Models\Region;
use App\Models\District;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    public static function form(Form $form): Form
    {
        // Fetch the ID of the 'NCR' region
        $ncrId = Region::where('name', 'NCR')->value('id');

        return $form
            ->schema([
                TextInput::make('name')->required(),
                Select::make('region_id')
                    ->label('Region')
                    ->options([
                        $ncrId => 'NCR'
                    ])
                    ->default($ncrId)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label('District Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("region.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
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
                            Column::make('name')->heading('District Name'),
                            Column::make('created_at')->heading('Date Created'),
                        ])
                        ->withFilename(date('Y-m-d') . ' - Districts')
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
            'index' => Pages\ListDistricts::route('/'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
            'create' => Pages\CreateDistrict::route('/create'),
           
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Remove global scopes like SoftDeletingScope for the query
        $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        // Apply general filters
        $query->where('name', '!=', 'Not Applicable');

        // Check if the current route is for editing by looking for the 'edit' segment in the URL
        // Adjust this pattern based on your route structure
        if (!request()->is('*/edit') && $regionId = request()->route('record')) {
            if (is_numeric($regionId)) {
                $query->where('region_id', (int) $regionId);
            }
        }

        return $query;
    }

}
