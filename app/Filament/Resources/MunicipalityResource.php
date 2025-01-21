<?php

namespace App\Filament\Resources;

use App\Models\District;
use App\Models\Province;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Municipality;
use Filament\Resources\Resource;
use App\Services\NotificationHandler;
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
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MunicipalityResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static ?string $navigationGroup = 'TARGET DATA INPUT';

    protected static ?string $navigationParentItem = 'Regions';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Municipality')
                    ->placeholder('Enter municipality name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Municipality'),

                TextInput::make("code")
                    ->label('PSG Code')
                    ->placeholder('Enter PSG code')
                    ->autocomplete(false)
                    ->integer()
                    ->validationAttribute('PSG Code'),

                TextInput::make('class')
                    ->label('Municipality Class')
                    ->placeholder('Enter municipality class')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Municipality Class'),

                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No provinces available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province')
                    ->afterStateUpdated(function (callable $set) {
                        $set('district_id', []);
                    })
                    ->reactive()
                    ->live(),

                Select::make('district_id')
                    ->label('District')
                    ->relationship('district', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->native(false)
                    ->options(function (callable $get) {
                        $selectedProvince = $get('province_id');

                        if (!$selectedProvince) {
                            return ['no_district' => 'No districts available. Select a province first.'];
                        }

                        return District::with('municipality')
                            ->where('province_id', $selectedProvince)
                            ->whereNot('name', 'Not Applicable')
                            ->get()
                            ->mapWithKeys(function ($district) {
                                $municipalityName = $district->underMunicipality->name ?? null;

                                return [
                                    $district->id => $municipalityName
                                        ? "{$district->name} - {$municipalityName}"
                                        : "{$district->name}"
                                ];
                            })
                            ->toArray() ?: ['no_district' => 'No districts available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_district')
                    ->reactive()
                    ->live(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No municipalities available')
            ->columns([
                TextColumn::make('code')
                    ->label('PSG Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->code ?? '-'),

                TextColumn::make('name')
                    ->label('Municipality')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('class')
                    ->label('Municipality Class')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $hasMunicipality = $record->district->contains(function ($district) {
                            return !is_null($district->underMunicipality->name ?? null);
                        });

                        if ($hasMunicipality) {
                            return $record->district->map(function ($district, $index) use ($record) {
                                $municipalityName = $district->underMunicipality->name ?? null;
                                $paddingTop = ($index > 0) ? 'padding-top: 15px;' : '';
                                $comma = ($index < $record->district->count() - 1) ? ',' : '';
                                $formattedDistrict = $municipalityName
                                    ? "{$district->name} - {$municipalityName}"
                                    : "{$district->name}";

                                return '<div style="' . $paddingTop . '">' . $formattedDistrict . $comma . '</div>';
                            })->implode('');
                        } else {
                            $districts = $record->district->pluck('name')->toArray();

                            $districtsHtml = array_map(function ($name, $index) use ($districts) {
                                $comma = ($index < count($districts) - 1) ? ', ' : '';
                                $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';
                                $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                                return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                            }, $districts, array_keys($districts));

                            return implode('', $districtsHtml);
                        }
                    })
                    ->html(),

                TextColumn::make('province.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('province.region.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()->action(function ($record) {
                        $record->delete();

                        NotificationHandler::sendSuccessNotification('Deleted', 'Municipality has been deleted successfully.');
                    }),
                    RestoreAction::make()->action(function ($record) {
                        $record->restore();

                        NotificationHandler::sendSuccessNotification('Restored', 'Municipality has been restored successfully.');
                    }),
                    ForceDeleteAction::make()->action(function ($record) {
                        $record->forceDelete();

                        NotificationHandler::sendSuccessNotification('Force Deleted', 'Municipality has been permanently deleted.');
                    }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected municipalities have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected municipalities have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected municipalities have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('code')
                                        ->heading('PSG Code')
                                        ->getStateUsing(function ($record) {
                                            return $record->code ?: '-';
                                        }),
                                    Column::make('name')
                                        ->heading('Municipality'),
                                    Column::make('class')
                                        ->heading('Municipality Class'),
                                    Column::make('district.name')
                                        ->heading('District'),
                                    Column::make('province.name')
                                        ->heading('Province'),
                                    Column::make('province.region.name')
                                        ->heading('Region'),
                                ])
                                ->withFilename(now()->format('m-d-Y') . ' - Municipalities'),
                        ]),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $districtId = request()->route('record');

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNot('name', 'Not Applicable')
            ->when($districtId, function (Builder $query) use ($districtId) {
                $query->whereHas('district', function (Builder $subQuery) use ($districtId) {
                    $subQuery->where('districts.id', $districtId);
                });
            })
            ->orderBy('province_id')
            ->orderBy('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMunicipalities::route('/'),
            'create' => Pages\CreateMunicipality::route('/create'),
            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
            'show' => Pages\ShowMunicipalities::route('/{record}'),
        ];
    }
}