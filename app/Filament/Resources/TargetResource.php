<?php

namespace App\Filament\Resources;

use App\Models\SubParticular;
use App\Models\Target;
use App\Models\TargetStatus;
use App\Models\Allocation;
use App\Models\ScholarshipProgram;
use App\Models\QualificationTitle;
use App\Models\Tvi;
use App\Models\Legislator;
use App\Models\Particular;
use App\Filament\Resources\TargetResource\Pages;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Actions\Action;
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

class TargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Pending Targets";

    protected static ?string $navigationIcon = 'heroicon-o-ellipsis-horizontal-circle';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(function ($record) {
                if ($record) {
                    return [
                        Select::make('legislator_id')
                            ->label('Attribution Sender')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function () {
                                return Legislator::where('status_id', 1)
                                    ->whereNull('deleted_at')
                                    ->pluck('name', 'id')
                                    ->toArray() ?: ['no_legislators' => 'No legislator available'];
                            })
                            ->disabled()
                            ->dehydrated(),

                        Select::make('allocation_legislator_id')
                            ->label('Legislator')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function () {
                                return Legislator::where('status_id', 1)
                                    ->whereNull('deleted_at')
                                    ->has('allocation')
                                    ->pluck('name', 'id')
                                    ->toArray() ?: ['no_legislators' => 'No legislator available'];
                            })
                            ->disabled()
                            ->dehydrated(),

                        Select::make('particular_id')
                            ->label('Particular')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $legislatorId = $get('allocation_legislator_id');

                                return $legislatorId
                                    ? self::getParticularOptions($legislatorId)
                                    : ['no_particular' => 'No particular available'];
                            })
                            ->disabled()
                            ->dehydrated(),

                        Select::make('scholarship_program_id')
                            ->label('Scholarship Program')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $legislatorId = $get('allocation_legislator_id');
                                $particularId = $get('particular_id');

                                return $legislatorId
                                    ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                    : ['no_scholarship_program' => 'No scholarship program available'];
                            })
                            ->disabled()
                            ->dehydrated(),

                        Select::make('allocation_year')
                            ->label('Appropriation Year')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $legislatorId = $get('allocation_legislator_id');
                                $particularId = $get('particular_id');
                                $scholarshipProgramId = $get('scholarship_program_id');

                                return $legislatorId && $particularId && $scholarshipProgramId
                                    ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                    : ['no_allocation' => 'No allocation available'];
                            })
                            ->disabled()
                            ->dehydrated(),

                        Select::make('appropriation_type')
                            ->label('Appropriation Type')
                            ->required()
                            ->markAsRequired(false)
                            ->options([
                                'Current' => 'Current',
                                'Continuing' => 'Continuing',
                            ])
                            ->disabled()
                            ->dehydrated(),

                        Select::make('tvi_id')
                            ->label('Institution')
                            ->relationship('tvi', 'name')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(function () {
                                return TVI::whereNot('name', 'Not Applicable')
                                    ->pluck('name', 'id')
                                    ->toArray() ?: ['no_tvi' => 'No institution available'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_tvi'),

                        Select::make('qualification_title_id')
                            ->label('Qualification Title')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(function ($get) {
                                $scholarshipProgramId = $get('scholarship_program_id');

                                return $scholarshipProgramId
                                    ? self::getQualificationTitles($scholarshipProgramId)
                                    : ['no_qualification_title' => 'No qualification title available. Select a scholarship program first.'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

                        Select::make('abdd_id')
                            ->label('ABDD Sector')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            ->options(function ($get) {
                                $tviId = $get('tvi_id');

                                return $tviId
                                    ? self::getAbddSectors($tviId)
                                    : ['no_abddd' => 'No ABDD sector available. Select an institution first.'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_abddd'),

                        TextInput::make('number_of_slots')
                            ->label('Number of Slots')
                            ->placeholder('Enter number of slots')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->rules(['min: 10', 'max: 25'])
                            ->validationAttribute('Number of Slots')
                            ->validationMessages([
                                'min' => 'The number of slots must be at least 10.',
                                'max' => 'The number of slots must not exceed 25.'
                            ]),
                    ];
                } else {
                    return [
                        Repeater::make('targets')
                            ->schema([
                                Select::make('legislator_id')
                                    ->label('Attribution Sender')
                                    ->options(function () {
                                        $houseSpeakerIds = SubParticular::whereIn('name', ['House Speaker', 'House Speaker (LAKAS)'])
                                            ->pluck('id');

                                        $legislators = Legislator::where('status_id', 1)
                                            ->whereNull('deleted_at')
                                            ->has('allocation')
                                            ->whereHas('particular', function ($query) use ($houseSpeakerIds) {
                                                $query->whereIn('sub_particular_id', $houseSpeakerIds);
                                            })
                                            ->pluck('name', 'id')
                                            ->toArray();

                                        return !empty($legislators) ? $legislators : ['no_legislators' => 'No legislator available'];
                                    })
                                    ->searchable(),

                                Select::make('attribution_particular_id')
                                    ->label('Sender Particular')
                                    ->options(function ($get) {
                                        $legislatorId = $get('legislator_id');

                                        if ($legislatorId) {
                                            return Particular::whereHas('legislator', function ($query) use ($legislatorId) {
                                                $query->where('legislator_particular.legislator_id', $legislatorId);
                                            })
                                            ->with('subParticular')
                                            ->get()
                                            ->pluck('subParticular.name', 'id')
                                            ->toArray();
                                        }

                                        return [];
                                    })
                                    ->searchable(),
                                Select::make('allocation_legislator_id')
                                    ->label('Legislator')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function () {
                                        return Legislator::where('status_id', 1)
                                            ->whereNull('deleted_at')
                                            ->has('allocation')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_legislator' => 'No legislator available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_legislators')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (!$state) {
                                            $set('particular_id', null);
                                            $set('scholarship_program_id', null);
                                            $set('allocation_year', null);
                                            $set('appropriation_type', null);
                                            return;
                                        }

                                        $allocations = Allocation::where('legislator_id', $state)
                                            ->with('particular', 'scholarship_program')
                                            ->get();

                                        $particularOptions = $allocations->pluck('particular.name', 'particular.id')->toArray();
                                        $scholarshipProgramOptions = $allocations->pluck('scholarship_program.name', 'scholarship_program.id')->toArray();
                                        $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();

                                        $currentYear = now()->year;

                                        if (count($particularOptions) === 1) {
                                            $set('particular_id', key($particularOptions));
                                        } else {
                                            $set('particular_id', null);
                                        }

                                        if (count($scholarshipProgramOptions) === 1) {
                                            $set('scholarship_program_id', key($scholarshipProgramOptions));
                                        } else {
                                            $set('scholarship_program_id', null);
                                        }

                                        $particularId = $particularOptions ? key($particularOptions) : null;
                                        $scholarshipProgramId = $scholarshipProgramOptions ? key($scholarshipProgramOptions) : null;

                                        if ($particularId && $scholarshipProgramId) {
                                            if (count($allocations) === 1) {
                                                $set('allocation_year', key($appropriationYearOptions));

                                                if (key($appropriationYearOptions) == $currentYear) {
                                                    $set('appropriation_type', 'Current');
                                                }
                                            } else {
                                                $set('allocation_year', null);
                                                $set('appropriation_type', null);
                                            }
                                        } else {
                                            $set('allocation_year', null);
                                            $set('appropriation_type', null);
                                        }
                                    })
                                    ->reactive()
                                    ->live(),

                                Select::make('particular_id')
                                    ->label('Particular')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $legislator_id = $get('allocation_legislator_id');
                                        $legislatorRecords = Legislator::find($legislator_id);

                                        if ($legislatorRecords) {
                                            // Get particulars with subParticular names
                                            $particulars = $legislatorRecords->particular()->with(['subParticular', 'district.municipality.province.region'])->get();

                                            if ($particulars->isNotEmpty()) {
                                                // Prepare options array
                                                $options = $particulars->mapWithKeys(function ($particular) {
                                                    $subParticularName = $particular->subParticular ? $particular->subParticular->name : 'No Sub Particular';
                                                    $fundSourceName = $particular->subParticular && $particular->subParticular->fundSource ? $particular->subParticular->fundSource->name : 'No Fund Source';
                                                    $districtName = $particular->district ? $particular->district->name : 'No District';
                                                    $municipalityName = $particular->district && $particular->district->municipality ? $particular->district->municipality->name : 'No Municipality';
                                                    $provinceName = $particular->district && $particular->district->municipality && $particular->district->municipality->province ? $particular->district->municipality->province->name : 'No Province';
                                                    $regionName = $particular->district && $particular->district->municipality && $particular->district->municipality->province && $particular->district->municipality->province->region ? $particular->district->municipality->province->region->name : 'No Region';
                                                    $partylistName = $particular->partylist ? $particular->partylist->name : 'No Partylist';


                                                    if ($fundSourceName === 'CO Legislator Funds') {
                                                        if ($subParticularName === 'Senator') {
                                                            return [$particular->id => "{$subParticularName}"];
                                                        } elseif ($subParticularName === 'District') {
                                                            if ($regionName === 'NCR') {
                                                                return [$particular->id => "{$subParticularName} - {$districtName}, {$municipalityName}"];
                                                            } else {
                                                                return [$particular->id => "{$subParticularName} - {$districtName}, {$provinceName}"];
                                                            }
                                                        } elseif ($subParticularName === 'Party-list') {
                                                            return [$particular->id => "{$partylistName}"];
                                                        }
                                                    } elseif ($fundSourceName === 'RO Regular') {
                                                        $regionName = $particular->district && $particular->district->municipality && $particular->district->municipality->province && $particular->district->municipality->province->region ? $particular->district->municipality->province->region->name : 'No Region';
                                                    } elseif ($fundSourceName === 'CO Regular') {
                                                        return [$particular->id => "{$subParticularName}"];
                                                    }

                                                    return [];
                                                })->toArray();

                                                return $options ?: ['no_particular' => 'No particular available'];
                                            }
                                        }

                                        return ['no_particular' => 'No particular available. Select a legislator first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_particular')
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if (!$state) {
                                            $set('scholarship_program_id', null);
                                            $set('allocation_year', null);
                                            $set('appropriation_type', null);
                                            return;
                                        }

                                        $legislator_id = $get('allocation_legislator_id');
                                        $allocations = Allocation::where('legislator_id', $legislator_id)
                                            ->where('particular_id', $state)
                                            ->with('particular', 'scholarship_program')
                                            ->get();

                                        $scholarshipProgramOptions = $allocations->pluck('scholarship_program.name', 'scholarship_program.id')->toArray();
                                        $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();

                                        $currentYear = now()->year;

                                        if (count($allocations) === 1) {
                                            $set('scholarship_program_id', key($scholarshipProgramOptions));
                                            $set('allocation_year', key($appropriationYearOptions));

                                            if (key($appropriationYearOptions) == $currentYear) {
                                                $set('appropriation_type', 'Current');
                                            }
                                        } else {
                                            $set('scholarship_program_id', null);
                                            $set('allocation_year', null);
                                            $set('appropriation_type', null);
                                        }
                                    })
                                    ->reactive()
                                    ->live(),

                                Select::make('scholarship_program_id')
                                    ->label('Scholarship Program')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('allocation_legislator_id');
                                        $particularId = $get('particular_id');

                                        return $legislatorId
                                            ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                            : ['no_scholarship_program' => 'No scholarship program available. Select a particular first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if (!$state) {
                                            $set('allocation_year', null);
                                            $set('appropriation_type', null);
                                            return;
                                        }

                                        $legislator_id = $get('allocation_legislator_id');
                                        $particular_id = $get('particular_id');
                                        $allocations = Allocation::where('legislator_id', $legislator_id)
                                            ->where('particular_id', $particular_id)
                                            ->where('scholarship_program_id', $state)
                                            ->with('particular', 'scholarship_program')
                                            ->get();

                                        $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();

                                        $currentYear = now()->year;

                                        if (count($allocations) === 1) {
                                            $set('allocation_year', key($appropriationYearOptions));

                                            if (key($appropriationYearOptions) == $currentYear) {
                                                $set('appropriation_type', 'Current');
                                            }
                                        } else {
                                            $set('allocation_year', null);
                                            $set('appropriation_type', null);
                                        }
                                    })
                                    ->reactive()
                                    ->live(),

                                Select::make('allocation_year')
                                    ->label('Appropriation Year')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('allocation_legislator_id');
                                        $particularId = $get('particular_id');
                                        $scholarshipProgramId = $get('scholarship_program_id');

                                        return $legislatorId
                                            ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                            : ['no_allocation' => 'No appropriation year available. Select a scholarship program first'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_allocation')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('appropriation_type', null);

                                        $appropriationType = self::getAppropriationTypeOptions($state);

                                        $set('appropriationType', $appropriationType);

                                        if (count($appropriationType) === 1) {
                                            $set('appropriation_type', key($appropriationType));
                                        }
                                    })
                                    ->reactive()
                                    ->live(),

                                Select::make('appropriation_type')
                                    ->label('Allocation Type')
                                    ->required()
                                    ->options(function ($get) {
                                        return ([
                                            "Current" => "Current",
                                            "Continuing" => "Continuing"
                                        ]);
                                    })
                                    ->reactive()
                                    ->live(),

                                Select::make('tvi_id')
                                    ->label('Institution')
                                    ->relationship('tvi', 'name')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function () {
                                        return TVI::whereNot('name', 'Not Applicable')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_tvi' => 'No institution available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_tvi'),

                                Select::make('qualification_title_id')
                                    ->label('Qualification Title')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $scholarshipProgramId = $get('scholarship_program_id');

                                        return $scholarshipProgramId
                                            ? self::getQualificationTitles($scholarshipProgramId)
                                            : ['no_qualification_title' => 'No qualification title available. Select a scholarship program first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

                                Select::make('abdd_id')
                                    ->label('ABDD Sector')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->options(function ($get) {
                                        $tviId = $get('tvi_id');

                                        return $tviId
                                            ? self::getAbddSectors($tviId)
                                            : ['no_abddd' => 'No ABDD sector available. Select an institution first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_abddd'),


                                TextInput::make('number_of_slots')
                                    ->label('Number of Slots')
                                    ->placeholder('Enter number of slots')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->autocomplete(false)
                                    ->numeric()
                                    ->rules(['min: 10', 'max: 25'])
                                    ->validationAttribute('Number of Slots')
                                    ->validationMessages([
                                        'min' => 'The number of slots must be at least 10.',
                                        'max' => 'The number of slots must not exceed 25.'
                                    ]),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->addActionLabel('+')
                            ->cloneable(),
                        // TextInput::make('number_of_clones')
                        //     ->label('Number of Clones')
                        //     ->numeric()
                        //     ->minValue(1)
                        //     ->default(1)
                        //     ->helperText('Specify how many times you want to clone the form.')
                        //     ->reactive()
                        //     ->afterStateUpdated(function ($state, callable $set, $get) {
                        //         $numberOfClones = $state;

                        //         $targets = $get('targets') ?? [];
                        //         $currentCount = count($targets);

                        //         if ($numberOfClones > count($targets)) {
                        //             $baseForm = $targets[0] ?? [];

                        //             for ($i = count($targets); $i < $numberOfClones; $i++) {
                        //                 $targets[] = $baseForm;
                        //             }

                        //             $set('targets', $targets);
                        //         }elseif ($numberOfClones < $currentCount) {
                        //             $set('targets', array_slice($targets, 0, $numberOfClones));
                        //         }
                        //     })
                    ];
                }
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No targets available')
            ->columns([
                TextColumn::make('fund_source')
                    ->label('Fund Source')
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No legislator available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No particular available';
                        }

                        $particular = $record->allocation->particular;
                        $subParticular = $particular->subParticular;
                        $fundSource = $subParticular ? $subParticular->fundSource : null;

                        return $fundSource ? $fundSource->name : 'No fund source available';
                    }),

                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('legislator.name')
                    ->label('Attribution')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Source of Fund')
                    ->toggleable(),

                TextColumn::make('appropriation_type')
                    ->toggleable(),

                TextColumn::make('allocation.year')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('allocation.legislator.particular.subParticular')
                    ->label('Particular')
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No legislator available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No particular available';
                        }

                        $particular = $particulars->first();
                        $district = $particular->district;
                        $municipality = $district ? $district->municipality : null;

                        $districtName = $district ? $district->name : 'Unknown District';
                        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';

                        if ($districtName === 'Not Applicable') {
                            if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else {
                                return $particular->subParticular->name ?? 'Unknown SubParticular';
                            }
                        } else {
                            return "{$particular->subParticular->name} - {$districtName}, {$municipalityName}";
                        }
                    }),

                TextColumn::make('tvi.district.name')
                    ->toggleable(),

                TextColumn::make('tvi.district.municipality.name')
                    ->toggleable(),

                TextColumn::make('tvi.district.municipality.province.name')
                    ->toggleable(),

                TextColumn::make('tvi.district.municipality.province.region.name')
                    ->toggleable(),

                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.tviClass.tviType.name')
                    ->label('Institution Type')
                    ->toggleable(),

                TextColumn::make('tvi.tviClass.name')
                    ->label('Institution Class')
                    ->toggleable(),

                TextColumn::make('qualification_title.training_program.title')
                    ->label('Qualification Title')
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $qualificationTitle = $record->qualification_title;

                        if (!$qualificationTitle) {
                            return 'No qualification title available';
                        }

                        $trainingProgram = $qualificationTitle->trainingProgram;

                        return $trainingProgram ? $trainingProgram->title : 'No training program available';
                    }),

                TextColumn::make('abdd.name')
                    ->label('ABDD Sector')
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.tvet.name')
                    ->label('TVET Sector')
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector')
                    ->toggleable(),

                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('number_of_slots')
                    ->label('Number of Slots')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make('targetStatus.desc')
                    ->label('Status')
                    ->toggleable(),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id]),
            )
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    Action::make('viewHistory')
                        ->label('View History')
                        ->url(fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id]))
                        ->icon('heroicon-o-magnifying-glass'),
                    Action::make('viewComment')
                        ->label('View Comments')
                        ->url(fn($record) => route('filament.admin.resources.targets.showComments', ['record' => $record->id]))
                        ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                    Action::make('setAsCompliant')
                        ->label('Set as Compliant')
                        ->url(fn($record) => route('filament.admin.resources.compliant-targets.create', ['record' => $record->id]))
                        ->icon('heroicon-o-check-circle'),
                    Action::make('setAsNonCompliant')
                        ->label('Set as Non-Compliant')
                        ->url(fn($record) => route('filament.admin.resources.non-compliant-targets.create', ['record' => $record->id]))
                        ->icon('heroicon-o-x-circle'),
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
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('fund_source')
                                        ->heading('Fund Source')
                                        ->getStateUsing(function ($record) {
                                            $legislator = $record->allocation->legislator;

                                            if (!$legislator) {
                                                return 'No legislator available';
                                            }

                                            $particulars = $legislator->particular;

                                            if ($particulars->isEmpty()) {
                                                return 'No particular available';
                                            }

                                            $particular = $record->allocation->particular;
                                            $subParticular = $particular->subParticular;
                                            $fundSource = $subParticular ? $subParticular->fundSource : null;

                                            return $fundSource ? $fundSource->name : 'No fund source available';
                                        }),

                                    Column::make('allocation.legislator.name')
                                        ->heading('Legislator'),
                                    Column::make('allocation.soft_or_commitment')
                                        ->heading('Soft or Commitment'),
                                    Column::make('appropriation_type')
                                        ->heading('Appropriation Type'),
                                    Column::make('allocation.year')
                                        ->heading('Appropriation Year'),
                                    Column::make('allocation.particular')
                                        ->heading('Particular')
                                        ->getStateUsing(function ($record) {
                                            $particular = $record->allocation->particular;

                                            if (!$particular) {
                                                return ['no_particular' => 'No Particular Available'];
                                            }

                                            $district = $particular->district;
                                            $municipality = $district ? $district->municipality : null;
                                            $province = $municipality ? $municipality->province : null;

                                            $districtName = $district ? $district->name : 'Unknown District';
                                            $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
                                            $provinceName = $province ? $province->name : 'Unknown Province';

                                            $subParticular = $particular->subParticular->name ?? 'Unknown Sub-Particular';

                                            if ($subParticular === 'Party-list') {
                                                return "{$subParticular} - {$particular->partylist->name}";
                                            } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                                                return "{$subParticular}";
                                            } else {
                                                return "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
                                            }
                                        }),

                                    Column::make('tvi.name')
                                        ->heading('Institution'),
                                    Column::make('tvi.district.name')
                                        ->heading('District'),
                                    Column::make('tvi.district.municipality.name')
                                        ->heading('Municipality'),
                                    Column::make('tvi.district.municipality.province.name')
                                        ->heading('Province'),
                                    Column::make('tvi.district.municipality.province.region.name')
                                        ->heading('Region'),

                                    Column::make('tvi.address')
                                        ->heading('Address'),

                                    Column::make('tvi.tviClass.tviType.name')
                                        ->heading('TVI Type'),

                                    Column::make('tvi.tviClass.name')
                                        ->heading('Institution Class(A)'),

                                    Column::make('tvi.InstitutionClass.name')
                                        ->heading('Institution Class(B)'),

                                    Column::make('qualification_title.training_program.title')
                                        ->heading('Qualification Title')
                                        ->getStateUsing(function ($record) {
                                            $qualificationTitle = $record->qualification_title;

                                            $trainingProgram = $qualificationTitle->trainingProgram;

                                            return $trainingProgram ? $trainingProgram->title : 'No training program available';
                                        }),
                                    Column::make('allocation.scholarship_program.name')
                                        ->heading('Scholarship Program'),

                                    Column::make('abdd.name')
                                        ->heading('ABDD Sector'),

                                    Column::make('qualification_title.trainingProgram.priority.name')
                                        ->heading('Ten Priority Sector'),

                                    Column::make('qualification_title.trainingProgram.tvet.name')
                                        ->heading('TVET Sector'),


                                    Column::make('number_of_slots')
                                        ->heading('No. of slots'),

                                    Column::make('training_cost_per_slot')
                                        ->heading('Training Cost')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_training_cost_pcc'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('cost_of_toolkit_per_slot')
                                        ->heading('Cost of Toolkit')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_cost_of_toolkit_pcc'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('training_support_fund_per_slot')
                                        ->heading('Training Support Fund')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_training_support_fund'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('assessment_fee_per_slot')
                                        ->heading('Assessment Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_assessment_fee'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('entrepreneurship_fee_per_slot')
                                        ->heading('Entrepreneurship Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_entrepreneurship_fee'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('new_normal_assistance_per_slot')
                                        ->heading('New Normal Assistance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_new_normal_assistance'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('accident_insurance_per_slot')
                                        ->heading('Accident Insurance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_accident_insurance'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('book_allowance_per_slot')
                                        ->heading('Book Allowance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_book_allowance'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('uniform_allowance_per_slot')
                                        ->heading('Uniform Allowance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_uniform_allowance'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('misc_fee_per_slot')
                                        ->heading('Miscellaneous Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_misc_fee'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_amount_per_slot')
                                        ->heading('PCC')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_amount'))
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_training_cost_pcc')
                                        ->heading('Total Training Cost')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_cost_of_toolkit_pcc')
                                        ->heading('Total Cost of Toolkit')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_training_support_fund')
                                        ->heading('Total Training Support Fund')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_assessment_fee')
                                        ->heading('Total Assessment Fee')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_entrepreneurship_fee')
                                        ->heading('Total Entrepreneurship Fee')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_new_normal_assisstance')
                                        ->heading('Total New Normal Assistance')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_accident_insurance')
                                        ->heading('Total Accident Insurance')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),


                                    Column::make('total_book_allowance')
                                        ->heading('Total Book Allowance')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_uniform_allowance')
                                        ->heading('Total Uniform Allowance')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_misc_fee')
                                        ->heading('Total Miscellaneous Fee')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('total_amount')
                                        ->heading('Total PCC')
                                        ->formatStateUsing(fn($state) => self::formatCurrency($state)),

                                    Column::make('targetStatus.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Targets')
                        ]),
                ]),
            ]);

    }

    protected static function calculateCostPerSlot($record, $costProperty)
    {
        $totalCost = $record->{$costProperty};
        $slots = $record->number_of_slots;

        if ($slots > 0) {
            return $totalCost / $slots;
        }

        return 0;
    }

    protected static function formatCurrency($amount)
    {
        return '₱ ' . number_format($amount, 2, '.', ',');
    }
    protected static function getParticularOptions($legislatorId)
    {
        return Particular::whereHas('allocation', function ($query) use ($legislatorId) {
            $query->where('legislator_id', $legislatorId);
        })
            ->with('subParticular')
            ->get()
            ->mapWithKeys(function ($particular) {

                if ($particular->district->name === 'Not Applicable') {
                    if ($particular->subParticular->name === 'Partylist') {
                        return [$particular->id => $particular->subParticular->name . " - " . $particular->partylist->name];
                    } else {
                        return [$particular->id => $particular->subParticular->name];
                    }
                } else {
                    return [$particular->id => $particular->subParticular->name . " - " . $particular->district->name . ', ' . $particular->district->municipality->name];
                }

            })
            ->toArray() ?: ['no_particular' => 'No particular available'];
    }

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId)
    {
        return ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
            $query->where('legislator_id', $legislatorId)
                ->where('particular_id', $particularId);
        })
            ->pluck('name', 'id')
            ->toArray() ?: ['no_scholarship_program' => 'No scholarship program available'];
    }

    protected static function getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
    {
        $yearNow = date('Y');

        return Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->whereIn('year', [$yearNow, $yearNow - 1])
            ->pluck('year', 'year')
            ->toArray() ?: ['no_allocation' => 'No allocation available'];
    }

    protected static function getAppropriationTypeOptions($year)
    {
        $yearNow = date('Y');

        if ($year == $yearNow) {
            return ["Current" => "Current"];
        } elseif ($year == $yearNow - 1) {
            return ["Continuing" => "Continuing"];
        } else {
            return ["Unknown" => "Unknown"];
        }
    }

    protected static function getQualificationTitles($scholarshipProgramId)
    {
        return QualificationTitle::where('scholarship_program_id', $scholarshipProgramId)
            ->where('status_id', 1)
            ->whereNull('deleted_at')
            ->with('trainingProgram')
            ->get()
            ->pluck('trainingProgram.title', 'id')
            ->toArray();
    }

    protected static function getAbddSectors($tviId)
    {
        $tvi = Tvi::with(['district.municipality.province'])->find($tviId);

        if (!$tvi || !$tvi->district || !$tvi->district->municipality || !$tvi->district->municipality->province) {
            return ['' => 'No ABDD sector available'];
        }

        $abddSectors = $tvi->district->municipality->province->abdds()
            ->select('abdds.id', 'abdds.name')
            ->pluck('name', 'id')
            ->toArray();

        return empty($abddSectors) ? ['' => 'No ABDD sector available'] : $abddSectors;
    }

    // public function getFormattedParticularAttribute()
    // {
    //     $particular = $this->allocation->particular ?? null;

    //     if (!$particular) {
    //         return 'No particular available';
    //     }

    //     $district = $particular->district;
    //     $municipality = $district ? $district->municipality : null;
    //     $province = $municipality ? $municipality->province : null;

    //     $districtName = $district ? $district->name : 'Unknown District';
    //     $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
    //     $provinceName = $province ? $province->name : 'Unknown Province';

    //     $subParticular = $particular->subParticular->name ?? 'Unknown Sub-Particular';

    //     if ($subParticular === 'Party-list') {
    //         return "{$subParticular} - {$particular->partylist->name}";
    //     } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
    //         return "{$subParticular}";
    //     } else {
    //         return "{$subParticular} - {$districtName}, {$municipalityName}";
    //     }
    // }

    protected function getFormattedTotalAmountAttribute($total_amount)
    {
        return '₱' . number_format($this->$total_amount, 2, '.', ',');
    }

    protected function getFormattedPerCapitaCostAttribute($total_training_cost_pcc)
    {
        return '₱' . number_format($this->$total_training_cost_pcc, 2, '.', ',');
    }

    protected function getFormattedScholarshipProgramAttribute($allocation)
    {
        return $this->$allocation->scholarship_program->name ?? 'No scholarship program available';
    }
    protected function getFundSource($abddSectorsallocation)
    {
        $legislator = $this->$$abddSectorsallocation->legislator;

        if (!$legislator) {
            return 'No legislator available';
        }

        $particulars = $legislator->particular;

        if ($particulars->isEmpty()) {
            return 'No particular available';
        }

        $particular = $this->$abddSectorsallocation->particular;
        $subParticular = $particular->subParticular;
        $fundSource = $subParticular ? $subParticular->fundSource : null;

        return $fundSource ? $fundSource->name : 'No fund source available';
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTargets::route('/'),
            'create' => Pages\CreateTarget::route('/create'),
            'edit' => Pages\EditTarget::route('/{record}/edit'),
            'showHistory' => Pages\ShowHistory::route('/{record}/history'),
            'showComments' => Pages\ShowComments::route('/{record}/comments'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');
        $nonCompliantStatus = TargetStatus::where('desc', 'Pending')->first();

        if ($nonCompliantStatus) {
            $query->withoutGlobalScopes([SoftDeletingScope::class])
                ->where('target_status_id', '=', $nonCompliantStatus->id);

            if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
                $query->where('region_id', (int) $routeParameter);
            }
        }

        return $query;
    }

}
