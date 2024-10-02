<?php

namespace App\Filament\Resources;

use App\Models\District;
use App\Models\Municipality;
use App\Filament\Resources\DistrictResource\Pages;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Regions";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('District')
                    ->placeholder(placeholder: 'Enter district name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('District'),

                Select::make('municipality_id')
                    ->relationship('municipality', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->default(fn($get) => request()->get('municipality_id'))
                    ->native(false)
                    ->options(function () {
                        $municipality = Municipality::where('name', '!=', 'Not Applicable')->pluck('name', 'id')->toArray();
                        return !empty($municipality) ? $municipality : ['no_municipality' => 'No Municipality Available'];
                    })
                    ->disableOptionWhen(fn ($value) => $value === 'no_municipality'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No districts available')
            ->columns([
                TextColumn::make('name')
                    ->label('District')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('municipality.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('municipality.province.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('municipality.province.region.name')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed())
                        ->successNotificationTitle('District updated successfully.')
                        ->failureNotificationTitle('Failed to update the district.'),
                    DeleteAction::make()
                        ->successNotificationTitle('District deleted successfully.')
                        ->failureNotificationTitle('Failed to delete the district.'),
                    RestoreAction::make()
                        ->successNotificationTitle('District restored successfully.')
                        ->failureNotificationTitle('Failed to restore the district.'),
                    ForceDeleteAction::make()
                        ->successNotificationTitle('District permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete the district.'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Districts deleted successfully.')
                        ->failureNotificationTitle('Failed to delete districts.'),
                    ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Districts permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete districts.'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Districts restored successfully.')
                        ->failureNotificationTitle('Failed to restore districts.'),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')->heading('District'),
                                Column::make('municipality.name')->heading('Municipality'),
                                Column::make('municipality.province.name')->heading('Province'),
                                Column::make('municipality.province.region.name')->heading('Region'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - District')
                    ]),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        $query->withoutGlobalScopes([SoftDeletingScope::class])
        ->where('name', '!=', 'Not Applicable');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('municipality_id', (int) $routeParameter);
        }

        return $query;
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
