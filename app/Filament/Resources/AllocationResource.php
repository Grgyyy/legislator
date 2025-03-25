<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomAllocationExport;
use App\Filament\Resources\AllocationResource\Pages;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\ScholarshipProgram;
use App\Models\Status;
use App\Models\TargetStatus;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

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
                    ])
                    ->reactive()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if (!$state) {
                            $set('attributor_id', null);
                            $set('attributor_particular_id', null);
                            $set('legislator_id', null);
                            $set('particular_id', null);

                            return;
                        }
                    })
                    ->validationAttribute('source of fund'),

                Select::make('attributor_id')
                    ->label('Attributor')
                    ->required(function ($get) {
                        return $get('soft_or_commitment') === 'Commitment';
                    })
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function () {
                        $activeStatusId = Status::where('desc', 'Active')->value('id');

                        return Legislator::whereHas('particular.subParticular', function ($query) {
                            $query->whereIn('name', ['House Speaker', 'Senator', 'RO Regular', 'CO Regular']);
                        })
                            ->where('status_id', $activeStatusId)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_legislator' => 'No attributors available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                    ->afterStateUpdated(function (callable $set, $state) {
                        if (!$state) {
                            $set('attributor_particular_id', null);
                        }

                        $particulars = self::getParticularOptions($state, true);

                        $set('attributor_particular_id', null);
                        $set('particularOptions', $particulars);

                        if ($particulars && count($particulars) === 1) {
                            $set('attributor_particular_id', key($particulars));
                        }
                    })
                    ->reactive()
                    ->live()
                    ->visible(function ($get) {
                        return $get('soft_or_commitment') === 'Commitment';
                    })
                    ->validationAttribute('attributor'),

                Select::make('attributor_particular_id')
                    ->label('Attributor Particular')
                    ->required(function ($get) {
                        return $get('attributor_id') ? true : false;
                    })
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function ($get) {
                        $legislatorId = $get('attributor_id');

                        return $legislatorId
                            ? self::getParticularOptions($legislatorId, true)
                            : ['no_particular' => 'No particulars available. Select a legislator first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_particular')
                    ->reactive()
                    ->live()
                    ->visible(function ($get) {
                        return $get('soft_or_commitment') === 'Commitment';
                    })
                    ->validationAttribute('attributor particular'),

                Select::make('legislator_id')
                    ->label('Legislator')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function ($get) {
                        $attributor_id = $get('attributor_id');

                        return Legislator::whereNot('id', $attributor_id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_legislator' => 'No legislators available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                    ->afterStateUpdated(function (callable $set, $state) {
                        if (!$state) {
                            $set('particular_id', null);
                            return;
                        }

                        $particulars = self::getParticularOptions($state, false);

                        $set('particular_id', null);

                        $set('particularOptions', $particulars);

                        if (count($particulars) === 1) {
                            $set('particular_id', key($particulars));
                        }
                    })
                    ->reactive()
                    ->live()
                    ->validationAttribute('legislator'),

                Select::make('particular_id')
                    ->label('Particular')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function ($get) {
                        $legislatorId = $get('legislator_id');

                        return $legislatorId
                            ? self::getParticularOptions($legislatorId, false)
                            : ['no_particular' => 'No particulars available. Select a legislator first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_particular')
                    ->reactive()
                    ->live()
                    ->validationAttribute('particular'),

                Select::make('scholarship_program_id')
                    ->label('Scholarship Program')
                    ->relationship('scholarship_program', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function () {
                        return ScholarshipProgram::all()
                            ->sortBy('name')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                    ->validationAttribute('scholarship program'),

                TextInput::make('allocation')
                    ->label('Allocation')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->prefix('₱')
                    ->default(0)
                    ->minValue(1)
                    ->maxValue(999999999999.99)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->reactive()
                    ->live()
                    // ->disabled(fn($livewire) => $livewire->isEdit())
                    ->dehydrated()
                    ->validationAttribute('Allocation')
                    ->validationMessages([
                        'min' => 'The allocation must be at least ₱1.00',
                        'max' => 'The allocation cannot exceed ₱999,999,999,999.99.'
                    ])
                    ->validationAttribute('allocation'),

                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->currencyMask(thousandSeparator: '', precision: 0)
                    ->default(date('Y'))
                    ->rules(['min:' . date('Y'), 'digits: 4'])
                    ->validationAttribute('year')
                    ->validationMessages([
                        'min' => 'The allocation year must be at least ' . date('Y') . '.',
                    ])
                    ->validationAttribute('year'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No allocations available')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('soft_or_commitment')
                    ->label('Source of Fund')
                    ->sortable()
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
                        $regionName = $record->attributorParticular->district->province->region->name ?? "-";

                        if ($particularName === 'RO Regular' || $particularName === 'CO Regular') {
                            return $particularName . ' - ' . $regionName;
                        } else {
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
                        $district = $particular->district ?? null;
                        $municipalityName = $district->underMunicipality->name ?? null;

                        if ($district->name === 'Not Applicable') {
                            if ($particular->subParticular->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                                return "{$particular->subParticular->name} - {$particular->district->province->region->name}";
                            } else {
                                return $particular->subParticular->name;
                            }
                        } else {
                            if ($municipalityName === null) {
                                return "{$particular->subParticular->name} - {$district->name}, {$district->province->name}";
                            } else {
                                return "{$particular->subParticular->name} - {$district->name}, {$municipalityName}, {$district->province->name}";
                            }
                        }
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

                TextColumn::make("expended_funds")
                    ->label('Funds Expended')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->getStateUsing(function ($record) {
                        $nonCompliantRecord = TargetStatus::where('desc', 'Non-Compliant')->first();
                        $fundsExpended = $record->target->where('target_status_id', '!=', $nonCompliantRecord->id)->sum('total_amount');

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
                        ->label('Records')
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter allocation')),

                    Filter::make('allocation')
                        ->form([
                                Select::make('source_of_fund')
                                    ->label('Source of Fund')
                                    ->preload()
                                    ->searchable()
                                    ->options([
                                            'Soft' => 'Soft',
                                            'Commitment' => 'Commitment'
                                        ])
                                    ->reactive(),

                                Select::make('scholarship_program')
                                    ->label('Scholarship Program')
                                    ->preload()
                                    ->searchable()
                                    ->relationship('scholarship_program', 'name')
                                    ->reactive(),

                                TextInput::make('year')
                                    ->label('Allocation Year')
                                    ->placeholder('Enter allocation year')
                                    ->numeric()
                                    ->currencyMask(thousandSeparator: '', precision: 0)
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

                        Action::make('viewLogs')
                            ->label('View Logs')
                            ->url(fn($record) => route('filament.admin.resources.activity-logs.allocationLogs', ['record' => $record->id]))
                            ->icon('heroicon-o-document-text')
                            ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin', 'SMD Head']) || Auth::user()->can('view activity log')),

                        DeleteAction::make()
                            ->action(function ($record, $data) {
                                $record->delete();

                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($record)
                                    ->event('Deleted')
                                    ->withProperties([
                                            'soft_or_commitment' => $record->soft_or_commitment,
                                            'legislator' => $record->legislator->name,
                                            'attributor' => $record->attributor->name ?? null,
                                            'particular' => $record->particular_id,
                                            'attributor_particular' => $record->attributor_particular_id,
                                            'scholarship_program' => $record->scholarship_program->name,
                                            'allocation' => ltrim($record->allocation, '0'),
                                            'admin_cost' => ltrim($record->admin_cost, '0'),
                                            'balance' => ltrim($record->balance, '0'),
                                            'year' => $record->year,
                                        ])
                                    ->log(
                                        $record->attributor
                                        ? "An attribution allocation for '{$record->legislator->name}' has been deleted, attributed by '{$record->attributor->name}'."
                                        : "An allocation for '{$record->legislator->name}' has been successfully deleted."
                                    );

                                NotificationHandler::sendSuccessNotification('Deleted', 'Allocation has been deleted successfully.');
                            }),
                        RestoreAction::make()
                            ->action(function ($record, $data) {
                                $record->restore();

                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($record)
                                    ->event('Restored')
                                    ->withProperties([
                                            'soft_or_commitment' => $record->soft_or_commitment,
                                            'legislator' => $record->legislator->name,
                                            'attributor' => $record->attributor->name ?? null,
                                            'particular' => $record->particular_id,
                                            'attributor_particular' => $record->attributor_particular_id,
                                            'scholarship_program' => $record->scholarship_program->name,
                                            'allocation' => ltrim($record->allocation, '0'),
                                            'admin_cost' => ltrim($record->admin_cost, '0'),
                                            'balance' => ltrim($record->balance, '0'),
                                            'year' => $record->year,
                                        ])
                                    ->log(
                                        $record->attributor
                                        ? "An attribution allocation for '{$record->legislator->name}' has been restored, attributed by '{$record->attributor->name}'."
                                        : "An allocation for '{$record->legislator->name}' has been successfully restored."
                                    );

                                NotificationHandler::sendSuccessNotification('Restored', 'Allocation has been restored successfully.');
                            }),
                        ForceDeleteAction::make()
                            ->action(function ($record, $data) {
                                $record->forceDelete();

                                NotificationHandler::sendSuccessNotification('Force Deleted', 'Allocation has been deleted permanently.');
                            }),
                    ])
                ])
            ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->action(function ($records) {
                                $records->each->delete();

                                NotificationHandler::sendSuccessNotification('Deleted', 'Selected allocations have been deleted successfully.');
                            })
                            ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete allocation')),

                        RestoreBulkAction::make()
                            ->action(function ($records) {
                                $records->each->restore();

                                NotificationHandler::sendSuccessNotification('Restored', 'Selected allocations have been restored successfully.');
                            })
                            ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore allocation')),
                        ForceDeleteBulkAction::make()
                            ->action(function ($records) {
                                $records->each->forceDelete();

                                NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected allocations have been deleted permanently.');
                            })
                            ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete allocation')),
                        ExportBulkAction::make()
                            ->exports([
                                    CustomAllocationExport::make()
                                        ->withColumns([
                                                Column::make('soft_or_commitment')
                                                    ->heading('Source of Fund'),

                                                Column::make('attributor.name')
                                                    ->heading('Attributor')
                                                    ->getStateUsing(function ($record) {
                                                        $attributor = $record->attributor->name ?? "-";

                                                        return $attributor;
                                                    }),
                                                Column::make('attributorParticular.subParticular.name')
                                                    ->heading('Attributor Particular')
                                                    ->getStateUsing(function ($record) {
                                                        $particularName = $record->attributorParticular->subParticular->name ?? "-";
                                                        $regionName = $record->attributorParticular->district->province->region->name ?? "-";

                                                        if ($particularName === 'RO Regular' || $particularName === 'CO Regular') {
                                                            return $particularName . ' - ' . $regionName;
                                                        } else {
                                                            return $particularName;
                                                        }
                                                    }),

                                                Column::make('legislator.name')
                                                    ->heading('Legislator'),

                                                Column::make('particular.name')
                                                    ->heading('Particular')
                                                    ->getStateUsing(function ($record) {
                                                        $particular = $record->particular;
                                                        $district = $particular->district ?? null;
                                                        $municipalityName = $district->underMunicipality->name ?? null;

                                                        if ($district->name === 'Not Applicable') {
                                                            if ($particular->subParticular->name === 'Party-list') {
                                                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                                                            } elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                                                                return "{$particular->subParticular->name} - {$particular->district->province->region->name}";
                                                            } else {
                                                                return $particular->subParticular->name;
                                                            }
                                                        } else {
                                                            if ($municipalityName === null) {
                                                                return "{$particular->subParticular->name} - {$district->name}, {$district->province->name}";
                                                            } else {
                                                                return "{$particular->subParticular->name} - {$district->name}, {$municipalityName}, {$district->province->name}";
                                                            }
                                                        }
                                                    }),

                                                Column::make('scholarship_program.name')
                                                    ->heading('Scholarship Program'),

                                                Column::make('allocation')
                                                    ->heading('Allocation')
                                                    ->format('"₱ "#,##0.00'),

                                                Column::make('admin_cost')
                                                    ->heading('Admin Cost')
                                                    ->format('"₱ "#,##0.00'),

                                                Column::make('admin_cost_difference')
                                                    ->heading('Allocation - Admin Cost')
                                                    ->getStateUsing(function ($record) {
                                                        $allocation = $record->allocation ?? 0;
                                                        $adminCost = $record->admin_cost ?? 0;

                                                        $difference = $allocation - $adminCost;

                                                        return $difference;
                                                    })
                                                    ->format('"₱ "#,##0.00'),

                                                Column::make('expended_funds')
                                                    ->heading('Funds Expended')
                                                    ->getStateUsing(function ($record) {
                                                        $nonCompliantRecord = TargetStatus::where('desc', 'Non-Compliant')->first();
                                                        $fundsExpended = $record->target->where('target_status_id', '!=', $nonCompliantRecord->id)->sum('total_amount');

                                                        return $fundsExpended;
                                                    })
                                                    ->format('"₱ "#,##0.00'),

                                                Column::make('balance')
                                                    ->heading('Balance')
                                                    ->format('"₱ "#,##0.00'),

                                                Column::make('year')
                                                    ->heading('Year'),
                                            ])
                                        ->withFilename(date('m-d-Y') . ' - Allocations')
                                ]),
                    ])
                        ->label('Select Action'),
                ]);
    }

    private static function getParticularOptions($legislatorId, $isAttributor)
    {
        if (!$legislatorId) {
            return;
        }

        $particulars = ['RO Regular', 'CO Regular', 'House Speaker', 'House Speaker (LAKAS)', 'Senator'];

        if ($isAttributor) {
            $legislator = Legislator::with([
                'particular' => function ($query) use ($particulars) {
                    $query->whereHas('subParticular', function ($subQuery) use ($particulars) {
                        $subQuery->whereIn('name', $particulars);
                    });
                },
                'particular.district.municipality'
            ])->find($legislatorId);
        } else {
            $legislator = Legislator::with('particular.district.municipality')->find($legislatorId);
        }

        if (!$legislator) {
            return;
        }

        return $legislator->particular->mapWithKeys(function ($particular) {
            $subParticular = $particular->subParticular->name ?? '';
            $formattedName = '';

            if (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                $formattedName = $subParticular;
            } elseif ($subParticular === 'Party-list') {
                $partylistName = $particular->partylist->name ?? '';
                $formattedName = "{$subParticular} - {$partylistName}";
            } elseif ($subParticular === 'District') {
                $districtName = $particular->district->name ?? '';
                $municipalityName = $particular->district->underMunicipality->name ?? '';
                $provinceName = $particular->district->province->name ?? '';

                if ($municipalityName) {
                    $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
                } else {
                    $formattedName = "{$subParticular} - {$districtName}, {$provinceName}";
                }
            } elseif ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
                $districtName = $particular->district->name ?? '';
                $provinceName = $particular->district->province->name ?? '';
                $regionName = $particular->district->province->region->name ?? '';
                $formattedName = "{$subParticular} - {$regionName}";
            } else {
                $regionName = $particular->district->province->region->name ?? '';
                $formattedName = "{$subParticular} - {$regionName}";
            }

            return [$particular->id => $formattedName];
        })->toArray() ?: ['no_particular' => 'No particulars available'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        if (!$user->hasRole(['Super Admin', 'Admin'])) {
            $query->whereNull('deleted_at');
        }
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