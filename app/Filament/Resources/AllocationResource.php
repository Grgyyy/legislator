<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllocationResource\Pages;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\ScholarshipProgram;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('soft_or_commitment')
                    ->label('Source of Fund')
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->options([
                        'Soft' => 'Soft',
                        'Commitment' => 'Commitment'
                    ]),

                Select::make('attributor_id')
                    ->label('Attributor')
                    ->searchable()
                    ->required(function ($get) {
                        $soft_or_commitment = $get('soft_or_commitment');

                        if ($soft_or_commitment === 'Commitment') {
                            return true;
                        }
                        else {
                            return false;
                        }
                    })
                    ->markAsRequired(false)
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Legislator::all()->mapWithKeys(function ($record) {
                            return [$record->id => $record->name];
                        })->toArray() ?: ['no_legislator' => 'No legislators available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                    ->afterStateUpdated(function (callable $set, $state) {
                        if (!$state) {
                            $set('attributor_particular_id', null);
                        }
                        
                        $particulars = self::getParticularOptions($state);

                        $set('particularOptions', $particulars);

                        if ($particulars && count($particulars) === 1) {
                            $set('attributor_particular_id', key($particulars));
                        }
                    })
                    ->reactive()
                    ->live(),

                Select::make('attributor_particular_id')
                    ->label('Attributor Particular')
                    ->required(function ($get) {
                        $attributor = $get('attributor_id');
                        $attributor ? true : false;
                    })
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function ($get) {
                        $legislatorId = $get('attributor_id');

                        return $legislatorId
                            ? self::getParticularOptions($legislatorId)
                            : ['' => 'No particulars available. Select a legislator first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === '')
                    ->reactive()
                    ->live(),

                Select::make('legislator_id')
                    ->label('Legislator')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function ($get) {
                        $attributor_id = $get('attributor_id');

                        return Legislator::whereNot('id', $attributor_id)
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_legislator' => 'No legislators available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('particular_id', null);

                        $particulars = self::getParticularOptions($state);

                        $set('particularOptions', $particulars);

                        if (count($particulars) === 1) {
                            $set('particular_id', key($particulars));
                        }
                    })
                    ->reactive()
                    ->live(),

                Select::make('particular_id')
                    ->label('Particular')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function ($get) {
                        $legislatorId = $get('legislator_id');

                        return $legislatorId
                            ? self::getParticularOptions($legislatorId)
                            : ['no_particular' => 'No particulars available. Select a legislator first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_particular')
                    ->reactive()
                    ->live(),

                Select::make('scholarship_program_id')
                    ->label('Scholarship Program')
                    ->relationship('scholarship_program', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return ScholarshipProgram::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program'),

                TextInput::make('allocation')
                    ->label('Allocation')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->prefix('₱')
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(999999999999.99)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->reactive()
                    ->live()
                    // ->disabled(fn($livewire) => $livewire->isEdit())
                    ->dehydrated()
                    ->validationAttribute('Allocation')
                    ->validationMessages([
                        'max' => 'The allocation cannot exceed ₱999,999,999,999.99.'
                    ]),

                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->integer()
                    ->default(date('Y'))
                    ->rules(['min:' . date('Y'), 'digits: 4'])
                    ->validationAttribute('year')
                    ->validationMessages([
                        'min' => 'The allocation year must be at least ' . date('Y') . '.',
                    ]),

                // TextInput::make('balance')
                //     ->label('Balance')
                //     ->required()
                //     ->markAsRequired(false)
                //     ->autocomplete(false)
                //     ->hidden()
                //     ->numeric()
                //     ->default(0)
                //     ->prefix('₱')
                //     ->minValue(0)
                //     ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                //     ->reactive()
                //     ->live(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No allocations available')
            ->columns([
                TextColumn::make('soft_or_commitment')
                    ->label('Source of Fund')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('attributor.name')
                    ->label('Attributor')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $attributor = $record->attributor->name ?? "-";

                        return $attributor;
                    }),

                TextColumn::make('attributorParticular.subParticular.name')
                    ->label('Attributor Particular')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $particularName = $record->attributorParticular->subParticular->name ?? "-";
                        $regionName = $record->attributorParticular->district->province->region->name ??"-";

                        if ($particularName === 'RO Regular' || $particularName === 'CO Regular') {
                            return $particularName . ' - ' . $regionName;
                        }
                        else {
                            return $particularName;
                        }
                    }),

                TextColumn::make("legislator.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("particular.name")
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $particular = $record->particular;

                        if (!$particular) {
                            return ['no_particular' => 'No particulars available'];
                        }

                        $district = $particular->district;
                        $municipality = $district ? $district->underMunicipality : null;
                        $districtName = $district ? $district->name : 'Unknown District';
                        $municipalityName = $municipality ? $municipality->name : '';
                        $provinceName = $district ? $district->province->name : 'Unknown Province';
                        $regionName = $district ? $district->province->region->name : 'Unknown Region';

                        $subParticular = $particular->subParticular->name ?? 'Unknown SubParticular';

                        $formattedName = '';

                        if ($subParticular === 'Party-list') {
                            $partylistName = $particular->partylist->name ?? 'Unknown Party-list';
                            $formattedName = "{$subParticular} - {$partylistName}";
                        } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                            $formattedName = "{$subParticular}";
                        } elseif ($subParticular === 'District') {
                            if ($municipalityName) {
                                $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
                            } else {
                                $formattedName = "{$subParticular} - {$districtName}, {$provinceName}, {$regionName}";
                            }
                        } elseif ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
                            $formattedName = "{$subParticular} - {$regionName}";
                        } else {
                            $formattedName = "{$subParticular} - {$regionName}";
                        }

                        return $formattedName;
                    }),

                TextColumn::make("scholarship_program.name")
                    ->label('Scholarship Program')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("allocation")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make("admin_cost")
                    ->label('Admin Cost')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make('admin_cost_difference')
                    ->label('Allocation - Admin Cost')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->getStateUsing(function ($record) {
                        $allocation = $record->allocation ?? 0;
                        $adminCost = $record->admin_cost ?? 0;

                        $difference = $allocation - $adminCost;

                        return number_format($difference, 2);
                    }),

                // TextColumn::make("attribution_sent")
                //     ->label('Attribution Sent')
                //     ->sortable()
                //     ->toggleable()
                //     ->prefix('₱')
                //     ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                // TextColumn::make("attribution_received")
                //     ->label('Attribution Received')
                //     ->sortable()
                //     ->toggleable()
                //     ->prefix('₱')
                //     ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make("expended_funds")
                    ->label('Funds Expended')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->getStateUsing(function ($record) {
                        $fundsExpended = $record->target->sum('total_amount');
                
                        return number_format($fundsExpended, 2);
                    }),

                TextColumn::make("balance")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make("year")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),

                Filter::make('allocation')
                    ->form([
                        Select::make('source_of_fund')
                            ->label('Source of Fund')
                            ->placeholder('All')
                            ->options([
                                'Soft' => 'Soft',
                                'Commitment' => 'Commitment'
                            ])
                            ->reactive(),

                        Select::make('scholarship_program')
                            ->label('Scholarship Program')
                            ->placeholder('All')
                            ->relationship('scholarship_program', 'name')
                            ->reactive(),

                        TextInput::make('year')
                            ->label('Allocation Year')
                            ->placeholder('Enter allocation year')
                            ->integer()
                            ->reactive(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['source_of_fund'] ?? null,
                                fn(Builder $query, $source_of_fund) => $query->where('soft_or_commitment', $source_of_fund)
                            )
                            ->when(
                                $data['scholarship_program_id'] ?? null,
                                fn(Builder $query, $scholarship_program_id) => $query->where('scholarship_program_id', $scholarship_program_id)
                            )
                            ->when(
                                $data['year'] ?? null,
                                fn(Builder $query, $year) => $query->where('year', $year)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['year'])) {
                            $indicators[] = 'Allocation Year: ' . $data['year'];
                        }

                        if (!empty($data['scholarship_program'])) {
                            $indicators[] = 'Scholarship Program: ' . Optional(ScholarshipProgram::find($data['scholarship_program']))->name;
                        }

                        if (!empty($data['source_of_fund'])) {
                            $indicators[] = 'Source of Fund: ' . $data['source_of_fund'];
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Allocation has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Allocation has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Allocation has been deleted permanently.');
                        }),
                    Action::make('addAllocation')
                        ->modalContent(function (Allocation $record): HtmlString {
                            $particular = $record->particular;

                            if (!$particular) {
                                $formattedName = 'No particulars available';
                            } else {
                                $district = $particular->district;
                                $municipality = $district ? $district->underMunicipality : null;
                                $districtName = $district ? $district->name : 'Unknown District';
                                $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
                                $provinceName = $district ? $district->province->name : 'Unknown Province';
                                $regionName = $district ? $district->province->region->name : 'Unknown Region';

                                $subParticular = $particular->subParticular->name ?? 'Unknown SubParticular';

                                if ($subParticular === 'Party-list') {
                                    $partylistName = $particular->partylist->name ?? 'Unknown Party-list';
                                    $formattedName = "{$subParticular} - {$partylistName}";
                                } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                                    $formattedName = "{$subParticular}";
                                } elseif ($subParticular === 'District') {
                                    if ($municipalityName) {
                                        $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
                                    } else {
                                        $formattedName = "{$subParticular} - {$districtName}, {$provinceName}, {$regionName}";
                                    }
                                } elseif ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
                                    $formattedName = "{$subParticular} - {$regionName}";
                                } else {
                                    $formattedName = "{$subParticular} - {$regionName}";
                                }
                            }

                            $allocationFormatted = '₱ ' . number_format($record->allocation, 2, '.', ',');
                            $adminCostFormatted = '₱ ' . number_format($record->admin_cost, 2, '.', ',');
                            $balanceFormatted = '₱' . number_format($record->balance, 2, '.', ',');

                            return new HtmlString("
                                <div style='margin-bottom: 1rem; margin-top: 1rem; font-size: .9rem; display: grid; grid-template-columns: 1fr 2fr; gap: 10px;'>
                                    <div style='font-weight: bold;'>Legislator:</div>
                                    <div>{$record->legislator->name} <em>({$formattedName})</em></div>

                                    <div style='font-weight: bold;'>Balance:</div>
                                    <div>{$balanceFormatted}</div>

                                    <div style='font-weight: bold;'>Allocation:</div>
                                    <div>{$allocationFormatted}</div>

                                    <div style='font-weight: bold;'>Admin Cost:</div>
                                    <div>{$adminCostFormatted}</div>

                                    <div style='font-weight: bold;'>Allocation Year:</div>
                                    <div>{$record->year}</div>

                                    <div style='font-weight: bold;'>Scholarship Program:</div>
                                    <div>{$record->scholarship_program->name}</div>

                                    <div style='font-weight: bold;'>Source of Fund:</div>
                                    <div>{$record->soft_or_commitment}</div>
                                </div>
                                
                            ");
                        })
                        ->modalHeading('Add Allocation')
                        ->modalWidth(MaxWidth::TwoExtraLarge)
                        ->icon('heroicon-o-plus')
                        ->label('Add Allocation')
                        ->form([
                            TextInput::make('allocation')
                                ->label('Add Allocation')
                                ->autocomplete(false)
                                ->numeric()
                                ->prefix('₱')
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(function (Allocation $record) {
                                    return 999999999999.99 - $record->allocation;
                                })
                                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                ->validationMessages([
                                    'max' => 'The allocation cannot exceed ₱999,999,999,999.99.'
                                ]),
                        ])
                        ->action(function (array $data, Allocation $record): void {
                            $record->allocation += $data['allocation'];

                            $adminCost = $record->allocation * 0.02;

                            $record->admin_cost = $adminCost;

                            $record->balance = $record->allocation - $record->admin_cost;

                            $record->save();

                            NotificationHandler::sendSuccessNotification('Saved', 'Allocation has been added successfully.');
                        })
                        ->hidden(function (Allocation $record): bool {
                            $currentYear = Carbon::now()->year;
                            
                            return $record->year < $currentYear;
                        })
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected allocations have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected allocations have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected allocations have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('soft_or_commitment')
                                        ->heading('Soft of Commitment'),
                                    Column::make('attributor.name')
                                        ->heading('Attributor'),
                                    Column::make('legislator.name')
                                        ->heading('Legislator'),
                                    Column::make('particular.name')
                                        ->heading('Particular')
                                        ->getStateUsing(function ($record) {
                                            $particular = $record->particular;

                                            if (!$particular) {
                                                return ['no_particular' => 'No particulars available'];
                                            }

                                            $district = $particular->district;
                                            $municipality = $district ? $district->underMunicipality : null;
                                            $districtName = $district ? $district->name : 'Unknown District';
                                            $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
                                            $provinceName = $district ? $district->province->name : 'Unknown Province';
                                            $regionName = $district ? $district->province->region->name : 'Unknown Region';

                                            $subParticular = $particular->subParticular->name ?? 'Unknown SubParticular';

                                            $formattedName = '';

                                            if ($subParticular === 'Party-list') {
                                                $partylistName = $particular->partylist->name ?? 'Unknown Party-list';
                                                $formattedName = "{$subParticular} - {$partylistName}";
                                            } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                                                $formattedName = "{$subParticular}";
                                            } elseif ($subParticular === 'District') {
                                                if ($municipalityName) {
                                                    $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
                                                } else {
                                                    $formattedName = "{$subParticular} - {$districtName}, {$provinceName}, {$regionName}";
                                                }
                                            } elseif ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
                                                $formattedName = "{$subParticular} - {$regionName}";
                                            } else {
                                                $formattedName = "{$subParticular} - {$regionName}";
                                            }

                                            return $formattedName;
                                        }),
                                    Column::make('scholarship_program.name')
                                        ->heading('Scholarship Program'),
                                    Column::make('allocation')
                                        ->heading('Allocation')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('admin_cost')
                                        ->heading('Admin Cost')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('balance')
                                        ->heading('Balance')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    // Column::make('attribution_sent')
                                    //     ->heading('Attribution Sent')
                                    //     ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    // Column::make('attribution_received')
                                    //     ->heading('Attribution Received')
                                    //     ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('expended_funds')
                                        ->heading('Funds Expended')
                                        ->getStateUsing(function ($record) {
                                            $fundsExpended = $record->target->sum('total_amount');
                                    
                                            return '₱ ' . number_format($fundsExpended, 2, '.', ',');
                                        }),
                                    Column::make('year')
                                        ->heading('Year'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Allocations')
                        ]),
                ]),
            ]);
    }

    private static function getParticularOptions($legislatorId)
    {
        if (!$legislatorId) {
            return;
        }

        $legislator = Legislator::with('particular.district.municipality')->find($legislatorId);

        if (!$legislator) {
            return;
        }

        return $legislator->particular->mapWithKeys(function ($particular) {
            $subParticular = $particular->subParticular->name ?? 'Unknown SubParticular';
            $formattedName = '';

            if (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                $formattedName = $subParticular;
            } elseif ($subParticular === 'Party-list') {
                $partylistName = $particular->partylist->name ?? 'Unknown Party-list';
                $formattedName = "{$subParticular} - {$partylistName}";
            } elseif ($subParticular === 'District') {
                $districtName = $particular->district->name ?? 'Unknown District';
                $municipalityName = $particular->district->underMunicipality->name ?? 'Unknown Municipality';
                $provinceName = $particular->district->province->name ?? 'Unknown Province';

                if ($municipalityName) {
                    $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
                } else {
                    $formattedName = "{$subParticular} - {$districtName}, {$provinceName}";
                }
            } elseif ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
                $districtName = $particular->district->name ?? 'Unknown District';
                $provinceName = $particular->district->province->name ?? 'Unknown Province';
                $regionName = $particular->district->province->region->name ?? 'Unknown Region';
                $formattedName = "{$subParticular} - {$regionName}";
            } else {
                $regionName = $particular->district->province->region->name ?? 'Unknown Region';
                $formattedName = "{$subParticular} - {$regionName}";
            }

            return [$particular->id => $formattedName];
        })->toArray() ?: ['no_particular' => 'No particulars available'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllocations::route('/'),
            'create' => Pages\CreateAllocation::route('/create'),
            'edit' => Pages\EditAllocation::route('/{record}/edit'),
        ];
    }
}