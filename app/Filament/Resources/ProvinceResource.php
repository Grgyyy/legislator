<?php

namespace App\Filament\Resources;

use App\Models\Province;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\ProvinceResource\Pages;
use App\Models\Region;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Support\Facades\Log; // Include Log if you want to log exceptions
use Illuminate\Validation\Rule;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Regions";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Province')
                    ->autocomplete(false)
                    ->markAsRequired(false)
                    ->required(),
                Select::make('region_id')
                    ->relationship('region', 'name')
                    ->default(fn($get) => request()->get('region_id'))
                    ->options(function () {
                        $region = Region::where('name', '!=', 'Not Applicable')->pluck('name', 'id')->toArray();
                        return !empty($region) ? $region : ['no_region' => 'No Region Available'];
                    })
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->disableOptionWhen(fn($value) => $value === 'no_region'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No provinces yet')
            ->columns([
                TextColumn::make('name')
                    ->label('Province')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->url(fn($record) => route('filament.admin.resources.provinces.showMunicipalities', ['record' => $record->id])),
                TextColumn::make('region.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record) {
                            $record->delete();
                            return redirect()->route('filament.admin.resources.provinces.index');
                        }),

                    RestoreAction::make()
                        ->successNotificationTitle('Province record restored successfully'),
                    ForceDeleteAction::make()
                        ->successNotificationTitle('Province record permanently deleted'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Province records deleted successfully'),
                    ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Province records permanently deleted'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Province records restored successfully'),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')
                                    ->heading('Province'),
                                Column::make('region.name')
                                    ->heading('Region'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Province')
                    ]),
                ]),
            ]);
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
        ])
            ->where('name', '!=', 'Not Applicable');

        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('region_id', (int) $routeParameter);
        }

        return $query;
    }
}
