<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomDistrictExport;
use App\Filament\Resources\DistrictResource\Pages;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                Select::make('huc')
                    ->label('HUC District')
                    ->markAsRequired()
                    ->options(fn() => [
                        true => 'Yes',
                        false => 'No'
                    ])
                    ->reactive()
                    ->live(),

                TextInput::make('name')
                    ->label('District')
                    ->placeholder('Enter district name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('district'),

                TextInput::make("code")
                    ->label('PSG Code')
                    ->placeholder('Enter PSG code')
                    ->autocomplete(false)
                    ->numeric()
                    ->currencyMask(thousandSeparator: '', precision: 0)
                    ->validationAttribute('PSG code'),

                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->default(fn($get) => request()->get('province_id'))
                    ->native(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No provinces available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province')
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('municipality_id', null);
                    })
                    ->reactive()
                    ->live()
                    ->validationAttribute('province'),

                Select::make('municipality_id')
                    ->label('Municipality')
                    ->required(function ($get) {
                        $huc = $get('huc');

                        if ($huc) {
                            return true;
                        }

                        return false;
                    })
                    ->markAsRequired(false)
                    ->hidden(function ($get) {
                        $huc = $get('huc');

                        if ($huc) {
                            return false;
                        }

                        return true;
                    })
                    ->native(false)
                    ->searchable()
                    ->options(function ($get) {
                        $provinceId = $get('province_id');

                        if (!$provinceId) {
                            return ['no_municipality' => 'No municipalities available. Select a province first.'];
                        }

                        $province = Province::with('region')->find($provinceId);

                        if ($province) {
                            return Municipality::where('province_id', $provinceId)
                                ->pluck('name', 'id')
                                ->toArray() ?: ['no_municipality' => 'No municipalities available'];
                        }

                        return ['no_municipality' => 'No municipalities available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_municipality')
                    ->reactive()
                    ->live()
                    ->validationAttribute('municipality'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('province.region.name')
            ->emptyStateHeading('No districts available')
            ->columns([
                TextColumn::make('code')
                    ->label('PSG Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->code ?? '-'),

                TextColumn::make('name')
                    ->label('District')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('underMunicipality.name')
                    ->label('Municipality')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->underMunicipality ? $record->underMunicipality->name : '-';
                    }),

                TextColumn::make('province.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('province.region.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.municipalities.show', ['record' => $record->id]),
            )
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter district')),
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
                            CustomDistrictExport::make()
                                ->withColumns([
                                    Column::make('code')
                                        ->heading('PSG Code')
                                        ->getStateUsing(function ($record) {
                                            return $record->code ?: '-';
                                        }),
                                    Column::make('name')
                                        ->heading('District'),
                                    Column::make('underMunicipality.name')
                                        ->heading('Municipality')
                                        ->getStateUsing(function ($record) {
                                            return $record->underMunicipality ? $record->underMunicipality->name : '-';
                                        }),
                                    Column::make('province.name')
                                        ->heading('Province'),
                                    Column::make('province.region.name')
                                        ->heading('Region')
                                ])
                                ->withFilename(date('m-d-Y') . ' - District Export')
                        ])
                ])
                    ->label('Select Action'),
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
