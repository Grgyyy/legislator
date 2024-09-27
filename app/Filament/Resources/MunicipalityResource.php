<?php

namespace App\Filament\Resources;

use App\Models\Municipality;
use App\Models\Province;
use App\Filament\Resources\MunicipalityResource\Pages;
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

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Regions";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->label('Municipality')
                    ->placeholder(placeholder: 'Enter municipality name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Municipality'),

                Select::make("province_id")
                    ->relationship("province", "name")
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->default(fn($get) => request()->get('province_id'))
                    ->native(false)
                    ->options(function () {
                        return Province::where('name', '!=', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No Province Available'];
                    })
                    ->disableOptionWhen(fn ($value) => $value === 'no_province'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No municipalities available')
            ->columns([
                TextColumn::make("name")
                    ->label("Municipality")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->url(fn($record) => route('filament.admin.resources.municipalities.showDistricts', ['record' => $record->id])),
                
                TextColumn::make("province.name")
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make("province.region.name")
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
                        ->hidden(fn($record) => $record->trashed())
                        ->successNotificationTitle('Municipality updated successfully.')
                        ->failureNotificationTitle('Failed to update the municipality.'),
                    DeleteAction::make()
                        ->successNotificationTitle('Municipality deleted successfully.')
                        ->failureNotificationTitle('Failed to delete the municipality.'),
                    RestoreAction::make()
                        ->successNotificationTitle('Municipality restored successfully.')
                        ->failureNotificationTitle('Failed to restore the municipality.'),
                    ForceDeleteAction::make()
                        ->successNotificationTitle('Municipality permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete the municipality.'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Municipalities deleted successfully.')
                        ->failureNotificationTitle('Failed to delete municipalities.'),
                    ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Municipalities permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete municipalities.'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Municipalities restored successfully.')
                        ->failureNotificationTitle('Failed to restore municipalities.'),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')->heading('Municipality'),
                                Column::make('province.name')->heading('Province'),
                                Column::make('province.region.name')->heading('Region'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Municipality')
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
            $query->where('province_id', (int) $routeParameter);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMunicipalities::route('/'),
            'create' => Pages\CreateMunicipality::route('/create'),
            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
            'showDistricts' => Pages\ShowDistrict::route('/{record}/districts'),
        ];
    }
}