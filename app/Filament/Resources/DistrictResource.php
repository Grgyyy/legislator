<?php

namespace App\Filament\Resources;

use App\Models\District;
use App\Models\Province;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use App\Filament\Resources\DistrictResource\Pages;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Regions";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make('code')
                    ->label('Code')
                    ->placeholder('Enter district code')
                    ->autocomplete(false),

                TextInput::make('name')
                    ->label('District')
                    ->placeholder(placeholder: 'Enter district name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('District'),

                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No province Available'];
                    })
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $province = Province::with('region')->find($state);


                            $set('is_municipality_disabled', $province && $province->region->name !== 'NCR');
                            if ($province && $province->region->name !== 'NCR') {
                                $set('municipality_id', null);
                            }
                        } else {

                            $set('is_municipality_disabled', false);
                        }
                    }),

                Select::make('municipality_id')
                    ->label('Municipality')
                    ->markAsRequired(false)
                    ->options(function ($get) {
                        $provinceId = $get('province_id');
                        if (!$provinceId) {
                            return [];
                        }

                        $province = Province::with('region')->find($provinceId);
                        return ($province && $province->region->name !== 'NCR')
                            ? []
                            : \App\Models\Municipality::where('province_id', $provinceId)
                                ->pluck('name', 'id')
                                ->toArray();
                    })
                    ->reactive()
                    ->disabled(function ($get) {
                        $provinceId = $get('province_id');
                        if (!$provinceId) {
                            return false;
                        }

                        $province = Province::with('region')->find($provinceId);
                        return ($province && $province->region->name !== 'NCR');
                    })
                    ->required(function ($get) {
                        $provinceId = $get('province_id');
                        $province = Province::with('region')->find($provinceId);
                        return $province && $province->region->name !== 'NCR';
                    })
                    ->afterStateHydrated(function ($state, callable $set) {
                        $provinceId = request()->input('province_id') ?? null;
                        if ($provinceId) {
                            $province = Province::with('region')->find($provinceId);
                            if ($province && $province->region->name !== 'NCR') {
                                $set('municipality_id', 'NA');  // Set to 'NA' if NCR
                            }
                        }
                    })






            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no districts available')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('District')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('province.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('province.municipality.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('province.region.name')
                    ->searchable()
                    ->toggleable(),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.municipalities.showMunicipality', ['record' => $record->id]),
            )
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'District has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'District has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'District has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected districts have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected districts have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected districts have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
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
            ->whereNot('name', 'Not Applicable');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('province_id', (int) $routeParameter);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
            'showDistricts' => Pages\ShowDistrict::route('/{record}/districts'),
        ];
    }
}
