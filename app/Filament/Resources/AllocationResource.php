<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use App\Models\Allocation;
use Filament\Tables\Table;
use Filament\Resources\Resource;
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
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AllocationResource\Pages;
use App\Models\Legislator;
use App\Models\ScholarshipProgram;
use Filament\Tables\Filters\Filter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Select::make('soft_or_commitment')
                ->label('Soft / Commitment')
                ->options([
                    'Soft' => 'Soft',
                    'Commitment' => 'Commitment'
                ]),
            Select::make('legislator_id')
                ->label('Legislator')
                ->relationship('legislator', 'name')
                ->afterStateUpdated(function (callable $set, $state) {
                    $set('particular_id', null);

                    $particulars = self::getParticularOptions($state);
                    $set('particularOptions', $particulars);

                    if (count($particulars) === 1) {
                        $set('particular_id', key($particulars));
                    }
                })
                ->options(function () {
                    return Legislator::all()->pluck('name', 'id')->toArray() ?: ['no_legislator' => 'No Legislator Available'];
                })
                ->live()
                ->preload()
                ->searchable()
                ->required()
                ->markAsRequired(false)
                ->native(false)
                ->disableOptionWhen(fn ($value) => $value === 'no_legislator'),
            Select::make('particular_id')
                ->label('Particular')
                ->options(function ($get) {
                    $legislatorId = $get('legislator_id');

                    return $legislatorId
                        ? self::getParticularOptions($legislatorId)
                        : ['no_particular' => 'No Particular available. Select a legislator first.'];
                })
                ->reactive()
                ->preload()
                ->live()
                ->searchable()
                ->required()
                ->markAsRequired(false)
                ->native(false)
                ->disableOptionWhen(fn ($value) => $value === 'no_particular'),
            Select::make('scholarship_program_id')
                ->label('Scholarship Programs')
                ->relationship('scholarship_program', 'name')
                ->options(function () {
                        $scholarshipProgram = ScholarshipProgram::all()->pluck('name', 'id')->toArray();
                        return !empty($scholarshipProgram) ? $scholarshipProgram : ['no_scholarship_program' => 'No Scholarship Program Available'];
                    })
                ->preload()
                ->searchable()
                ->required()
                ->markAsRequired(false)
                ->native(false)
                ->disableOptionWhen(fn ($value) => $value === 'no_scholarship_program'),
            TextInput::make('allocation')
                ->label('Allocation')
                ->required()
                ->autocomplete(false)
                ->markAsRequired(false)
                ->numeric()
                ->default(0)
                ->prefix('₱')
                ->minValue(0)
                ->maxValue(999999999999.99)
                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                ->debounce(600)
                ->afterStateUpdated(function (callable $set, $state, $get) {
                    
                    $adminCost = $state * 0.02;
                    
                    $set('admin_cost', $adminCost);
                    $set('balance', $state - $adminCost);
                })
                ->validationAttribute('allocation')
                ->validationMessages([
                    'max' => 'The allocation cannot exceed ₱999,999,999,999.99.'
                ]),
            TextInput::make('admin_cost')
                ->label('Admin Cost')
                ->required()
                ->markAsRequired(false)
                ->numeric()
                ->reactive()
                ->default(0)
                ->prefix('₱')
                ->minValue(0)
                ->readOnly()
                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                ->afterStateUpdated(function (callable $set, $state, $get) {
                    $allocation = floatval($get('allocation'));
                    $set('balance', $allocation - $state);
                }),
            TextInput::make('year')
                ->label('Year')
                ->markAsRequired(false)
                ->required()
                ->numeric()
                ->rules(['min:' . date('Y'), 'digits: 4'])
                ->default(date('Y'))
                ->validationAttribute('year')
                ->validationMessages([
                    'min' => 'The allocation year must be at least ' . date('Y') . '.',
                ]),
            TextInput::make('balance')
                ->label('Balance')
                ->required()
                ->markAsRequired(false)
                ->numeric()
                ->default(0)
                ->prefix('₱')
                ->minValue(0)
                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                ->hidden()
                ->reactive(),
        ]);
    }

    private static function getParticularOptions($legislatorId)
    {
        if (!$legislatorId) {
            return [];
        }
    
        $legislator = Legislator::with('particular.district.municipality')->find($legislatorId);
    
        if (!$legislator) {
            return [];
        }
    
        return $legislator->particular->mapWithKeys(function ($particular) {
            $districtName = $particular->district->name ?? 'Unknown District';
            $municipalityName = $particular->district->municipality->name ?? 'Unknown Municipality';

            $subParticular = $particular->subParticular->name;
            
            if ($subParticular === 'Senator' || $subParticular === 'House Speaker' || $subParticular === 'House Speaker (LAKAS)') {
                $formattedName = "{$particular->subParticular->name}";
            }

            elseif ($subParticular === 'Partylist') {
                $formattedName = "{$particular->subParticular->name} - {$particular->partylist->name}"; 
            }

            else {
                $formattedName = "{$particular->subParticular->name} - {$districtName}, {$municipalityName}"; 
            }
    
            return [$particular->id => $formattedName];
        })->toArray();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No allocations yet')
            ->columns([
                TextColumn::make('soft_or_commitment'),
                TextColumn::make("legislator.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("particular.name")
                    ->getStateUsing(function ($record) {
                        $particular = $record->particular;

                        if (!$particular) {
                            return 'No Particular Available';
                        }

                        $district = $particular->district;
                        $municipality = $district ? $district->municipality : null;
                        $districtName = $district ? $district->name : 'Unknown District';
                        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';

                        $subParticular = $particular->subParticular->name;

                        if ($subParticular === 'Partylist') {
                            $formattedName = "{$particular->subParticular->name} - {$particular->partylist->name}";  
                        }

                        elseif ($subParticular === 'Senator' || $subParticular === 'House Speaker' || $subParticular === 'House Speaker (LAKAS)') {
                            $formattedName = "{$particular->subParticular->name}";  
                        }

                        else {
                            $formattedName = "{$particular->subParticular->name} - {$districtName}, {$municipalityName}";

                        }

                        return $formattedName;
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("scholarship_program.name")
                    ->label('Scholarship Program')
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
                TextColumn::make("balance")
                    ->label('Balance')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make("year")
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('legislator.name')
                                    ->heading('Legislator'),
                                Column::make('particular.name')
                                    ->heading('Particular'),
                                Column::make('scholarship_program.name')
                                    ->heading('Scholarship Program'),
                                Column::make('allocation')
                                    ->heading('Allocation'),
                                Column::make('admin_cost')
                                    ->heading('Admin Cost'),
                                Column::make('balance')
                                    ->heading('Balance'),
                                Column::make('year')
                                    ->heading('Year'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Allocation')
                    ]),

                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllocations::route('/'),
            'create' => Pages\CreateAllocation::route('/create'),
            'edit' => Pages\EditAllocation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
