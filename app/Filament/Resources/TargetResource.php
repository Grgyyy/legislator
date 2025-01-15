<?php

namespace App\Filament\Resources;

use App\Models\Abdd;
use App\Models\ProvinceAbdd;
use App\Services\NotificationHandler;
use Throwable;
use App\Models\Tvi;
use App\Models\Region;
use App\Models\Target;
use Filament\Forms\Form;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use Filament\Tables\Table;
use App\Models\DeliveryMode;
use App\Models\LearningMode;
use App\Models\TargetStatus;
use Filament\Actions\Action;
use App\Models\TargetHistory;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use App\Filament\Resources\TargetResource\Pages;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

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
                        TextInput::make('abscap_id')
                            ->label('Absorbative Capacity ID')
                            ->placeholder('Enter an Absorbative capacity ID')
                            ->required()
                            ->markAsRequired(false)
                            ->numeric(),

                        Select::make('legislator_id')
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
                                $legislatorId = $get('legislator_id');

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
                                $legislatorId = $get('legislator_id');
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
                                $legislatorId = $get('legislator_id');
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
                                    ->has('trainingPrograms')
                                    ->pluck('name', 'id')
                                    ->mapWithKeys(function ($name, $id) {
                                        // $formattedName = preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($name));
                                        $tvi = Tvi::find($id);
                                        return [$id => "{$tvi->school_id} - {$tvi->name}"];
                                    })
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
                                $tviId = $get('tvi_id');
                                $year = $get('allocation_year');

                                return $scholarshipProgramId
                                    ? self::getQualificationTitles($scholarshipProgramId, $tviId, $year)
                                    : ['no_qualification_title' => 'No qualification title available. Select a scholarship program first.'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

                        Select::make('abdd_id')
                            ->label('ABDD Sector')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            // ->options(function ($get) {
                            //     $tviId = $get('tvi_id');

                            //     return $tviId
                            //         ? self::getAbddSectors($tviId)
                            //         : ['no_abddd' => 'No ABDD sector available. Select an institution first.'];
                            // })
                            ->options(function () {
                                return Abdd::whereNull('deleted_at')
                                    ->pluck('name', 'id')
                                    ->toArray() ?: ['no_abdd' => 'No ABDD Sectors available'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_abddd'),

                        Select::make('delivery_mode_id')
                            ->label('Delivery Mode')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $deliveryModes = DeliveryMode::all();

                                return $deliveryModes->isNotEmpty()
                                    ? $deliveryModes->pluck('name', 'id')->toArray()
                                    : ['no_delivery_mode' => 'No delivery modes available.'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode'),

                        Select::make('learning_mode_id')
                            ->label('Learning Mode')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            ->options(function ($get) {
                                $deliveryModeId = $get('delivery_mode_id');
                                $learningModes = [];

                                if ($deliveryModeId) {
                                    $learningModes = DeliveryMode::find($deliveryModeId)
                                        ->learningMode
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }
                                return !empty($learningModes)
                                    ? $learningModes
                                    : ['no_learning_modes' => 'No learning modes available for the selected delivery mode.'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_learning_modes'),

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
                                TextInput::make('abscap_id')
                                    ->label('Absorbative Capacity ID')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->placeholder('Enter an Absorbative capacity ID')
                                    ->numeric(),
                                Select::make('legislator_id')
                                    ->label('Legislator')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function () {
                                        return Legislator::where('status_id', 1)
                                            ->whereNull('deleted_at')
                                            ->whereHas('allocation', function ($query) {
                                                $query->where('balance', '>', 0);
                                            })
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
                                        $legislator_id = $get('legislator_id');
                                        $legislatorRecords = Legislator::find($legislator_id);

                                        if ($legislatorRecords) {
                                            // Get particulars with subParticular names
                                            $particulars = $legislatorRecords->particular()->with(['subParticular', 'district.province.region'])->get();

                                            if ($particulars->isNotEmpty()) {
                                                // Prepare options array
                                                $options = $particulars->mapWithKeys(function ($particular) {
                                                    $subParticularName = $particular->subParticular ? $particular->subParticular->name : 'No Sub Particular';
                                                    $fundSourceName = $particular->subParticular && $particular->subParticular->fundSource ? $particular->subParticular->fundSource->name : 'No Fund Source';
                                                    $districtName = $particular->district ? $particular->district->name : 'No District';
                                                    $municipalityName = $particular->district && $particular->district->underMunicipality ? $particular->district->underMunicipality->name : 'No Municipality';
                                                    $provinceName = $particular->district && $particular->district && $particular->district->province ? $particular->district->province->name : 'No Province';
                                                    $regionName = $particular->district && $particular->district && $particular->district->province && $particular->district->province->region ? $particular->district->province->region->name : 'No Region';
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
                                                        } elseif ($subParticularName === 'House Speaker' || $subParticularName === 'House Speaker (LAKAS)') {
                                                            return [$particular->id => "{$subParticularName}"];
                                                        }
                                                    } elseif ($fundSourceName === 'RO Regular') {
                                                        $regionName = $particular->district?->province?->region ?? 'No Region';
                                                        return [$particular->id => "{$subParticularName} - {$regionName->name}"];
                                                    } elseif ($fundSourceName === 'CO Regular') {
                                                        $regionName = $particular->district?->province?->region ?? 'No Region';
                                                        return [$particular->id => "{$subParticularName} - {$regionName->name}"];
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

                                        $legislator_id = $get('legislator_id');
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
                                        $legislatorId = $get('legislator_id');
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

                                        $legislator_id = $get('legislator_id');
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
                                            : ['no_allocation' => 'No appropriation year available. Select a scholarship program first.'];
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
                                    ->markAsRequired(false)
                                    ->native(false)
                                    ->options(function ($get) {
                                        $year = $get('allocation_year');
                                        return $year
                                            ? self::getAppropriationTypeOptions($year)
                                            : ['no_allocation' => 'No appropriation type available. Select an appropriation year first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_allocation')
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
                                            ->has('trainingPrograms')
                                            ->pluck('name', 'id')
                                            ->mapWithKeys(function ($name, $id) {
                                                // $formattedName = preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($name));
                                                $tvi = Tvi::find($id);
                                                return [$id => "{$tvi->school_id} - {$tvi->name}"];
                                            })
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
                                        $tviId = $get('tvi_id');
                                        $year = $get('allocation_year');

                                        return $scholarshipProgramId
                                            ? self::getQualificationTitles($scholarshipProgramId, $tviId, $year)
                                            : ['no_qualification_title' => 'No qualification title available. Select a scholarship program first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

                                Select::make('abdd_id')
                                    ->label('ABDD Sector')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    // ->options(function ($get) {
                                    //     $tviId = $get('tvi_id');

                                    //     return $tviId
                                    //         ? self::getAbddSectors($tviId)
                                    //         : ['no_abddd' => 'No ABDD sector available. Select an institution first.'];
                                    // })
                                    // ->disableOptionWhen(fn($value) => $value === 'no_abddd')
                                    ->options(function () {
                                        return Abdd::whereNull('deleted_at')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_abdd' => 'No ABDD Sectors available'];
                                    }),

                                Select::make('delivery_mode_id')
                                    ->label('Delivery Mode')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->options(function () {
                                        $deliveryModes = DeliveryMode::all();

                                        return $deliveryModes->isNotEmpty()
                                            ? $deliveryModes->pluck('name', 'id')->toArray()
                                            : ['no_delivery_mode' => 'No delivery modes available.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode'),

                                Select::make('learning_mode_id')
                                    ->label('Learning Mode')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->options(function ($get) {
                                        $deliveryModeId = $get('delivery_mode_id');
                                        $learningModes = [];

                                        if ($deliveryModeId) {
                                            $learningModes = DeliveryMode::find($deliveryModeId)
                                                ->learningMode
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        }
                                        return !empty($learningModes)
                                            ? $learningModes
                                            : ['no_learning_modes' => 'No learning modes available for the selected delivery mode.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_learning_modes'),

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
                            ->maxItems(100)
                            ->columns(3)
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
                TextColumn::make('abscap_id')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('fund_source')
                    ->label('Fund Source')
                    ->searchable()
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

                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Source of Fund')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('appropriation_type')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.year')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.legislator.particular.subParticular')
                    ->label('Particular')
                    ->searchable()
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
                        $municipality = $district ? $district->underMunicipality : null;

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

                TextColumn::make('municipality.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('district.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.district.municipality.province.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.district.municipality.province.region.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn($state) => preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($state))),

                TextColumn::make('tvi.tviClass.tviType.name')
                    ->label('Institution Type')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.tviClass.name')
                    ->label('Institution Class')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title_code')
                    ->label('Qualification Code')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title_soc_code')
                    ->label('Qualification SOC Code')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title_name')
                    ->label('Qualification Title')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return $state;
                        }

                        $state = ucwords($state);

                        if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                            $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                return 'NC ' . strtoupper($matches[1]);
                            }, $state);
                        }

                        return $state;
                    }),

                TextColumn::make('abdd.name')
                    ->label('ABDD Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.tvet.name')
                    ->label('TVET Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('deliveryMode.name')
                    ->label('Delivery Mode')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('learningMode.name')
                    ->label('Learning Mode')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('number_of_slots')
                    ->label('Number of Slots')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->searchable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make('targetStatus.desc')
                    ->label('Status')
                    ->searchable()
                    ->toggleable()
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
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $allocation = $record->allocation;
                            $totalAmount = $record->total_amount;

                            $institution = $record->tvi;
                            $abdd = $record->abdd;

                            $provinceAbdd = ProvinceAbdd::where('abdd_id', $abdd->id)
                                ->where('province_id', $institution->district->province_id)
                                ->where('year', $allocation->year)
                                ->first();

                            $totalSlots = $record->number_of_slots;

                            $provinceAbdd->available_slots += $totalSlots;
                            $provinceAbdd->save();

                            $allocation->balance += $totalAmount;
                            $allocation->save();

                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Target has been deleted successfully.');
                        }),
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
                                    Column::make('abscap_id')
                                        ->heading('Absorptive Capacity'),

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

                                    Column::make('allocation.legislator.particular.subParticular')
                                        ->heading('Particular')
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
                                            $municipality = $district ? $district->underMunicipality : null;

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
                                    Column::make('municipality.name')
                                        ->heading('Municipality'),

                                    Column::make('district.name')
                                        ->heading('District'),

                                    Column::make('tvi.district.province.name')
                                        ->heading('Province'),

                                    Column::make('tvi.district.province.region.name')
                                        ->heading('Region'),

                                    Column::make('tvi.name')
                                        ->heading('Institution'),

                                    Column::make('tvi.tviClass.tviType.name')
                                        ->heading('Institution Type'),

                                    Column::make('tvi.tviClass.name')
                                        ->heading('Institution Class'),

                                    Column::make('qualification_title_code')
                                        ->heading('Qualification Code'),

                                    Column::make('qualification_title_soc_code')
                                        ->heading('Schedule of Cost Code'),

                                    Column::make('qualification_title_name')
                                        ->heading('Qualification Title'),

                                    Column::make('abdd.name')
                                        ->heading('ABDD Sector'),

                                    Column::make('qualification_title.trainingProgram.tvet.name')
                                        ->heading('TVET Sector'),

                                    Column::make('qualification_title.trainingProgram.priority.name')
                                        ->heading('Priority Sector'),

                                    Column::make('deliveryMode.name')
                                        ->heading('Delivery Mode'),

                                    Column::make('learningMode.name')
                                        ->heading('Learning Mode'),

                                    Column::make('allocation.scholarship_program.name')
                                        ->heading('Scholarship Program'),

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
                    } else if ($particular->subParticular->name === 'House Speaker' || $particular->subParticular->name === 'House Speaker (LAKAS)') {
                        return [$particular->id => $particular->subParticular->name];
                    } else {
                        return [$particular->id => $particular->subParticular->name];
                    }
                } else {
                    return [$particular->id => $particular->subParticular->name . " - " . $particular->district->name . ', ' . $particular->district->underMunicipality->name];
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
            ->where('year', '>=', $yearNow - 1)
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

    protected static function getQualificationTitles($scholarshipProgramId, $tviId, $year)
    {
        $tvi = Tvi::with(['district.province'])->find($tviId);

        if (!$tvi || !$tvi->district || !$tvi->district->province) {
            return ['' => 'No Skill Priority available'];
        }

        $skillPriorities = $tvi->district->province->skillPriorities()
            ->where('year', $year)
            ->where('available_slots', '>=', 10)
            ->pluck('training_program_id')
            ->toArray();

        if (empty($skillPriorities)) {
            return ['' => 'No Training Programs available for this Skill Priority.'];
        }

        $institutionPrograms = $tvi->trainingPrograms()
            ->pluck('training_program_id')
            ->toArray();

        if (empty($institutionPrograms)) {
            return ['' => 'No Training Programs available for this Institution.'];
        }

        $qualificationTitles =
            QualificationTitle::whereIn('training_program_id', $skillPriorities)
            ->whereIn('training_program_id', $institutionPrograms)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->where('status_id', 1)
            ->where('soc', 1)
            ->whereNull('deleted_at')
            ->with('trainingProgram')
            ->get()
            ->mapWithKeys(function ($qualification) {
                $title = $qualification->trainingProgram->title;

                // Check for 'NC' pattern and capitalize it
                if (preg_match('/\bNC\s+[I]{1,3}\b/i', $title)) {
                    $title = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                        return 'NC ' . strtoupper($matches[1]);
                    }, $title);
                }

                return [$qualification->id => "{$qualification->trainingProgram->soc_code} - {$qualification->trainingProgram->title}"];

            })
            ->toArray();

        return !empty($qualificationTitles) ? $qualificationTitles : ['' => 'No Qualification Titles available'];
    }



    // protected static function getAbddSectors($tviId)
    // {
    //     $tvi = Tvi::with(['district.province'])->find($tviId);

    //     if (!$tvi || !$tvi->district || !$tvi->district->province) {
    //         return ['' => 'No ABDD sector available'];
    //     }

    //     $abddSectors = $tvi->district->province->abdds()
    //         ->select('abdds.id', 'abdds.name')
    //         ->pluck('name', 'id')
    //         ->toArray();

    //     return empty($abddSectors) ? ['' => 'No ABDD sector available'] : $abddSectors;
    // }

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
        $pendingStatus = TargetStatus::where('desc', 'Pending')->first();

        if ($pendingStatus) {
            // Ensure proper relationship for qualification_title
            $query->withoutGlobalScopes([SoftDeletingScope::class])
                ->where('target_status_id', '=', $pendingStatus->id)
                ->whereHas('qualification_title', function ($subQuery) {
                    $subQuery->where('soc', 1); // Assuming 'qualificationTitle' is the relationship name
                })
                ->whereNull('attribution_allocation_id');

            // Add region filter if valid route parameter
            if (!request()->is('*/edit') && $routeParameter && filter_var($routeParameter, FILTER_VALIDATE_INT)) {
                $query->where('region_id', (int) $routeParameter);
            }
        }

        return $query;
    }


    // public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery();
    //     $routeParameter = request()->route('record');
    //     $pendingStatus = TargetStatus::where('desc', 'Pending')->first();
    //     $user = auth()->user();

    //     if ($pendingStatus) {
    //         $query->withoutGlobalScopes([SoftDeletingScope::class])
    //             ->where('target_status_id', '=', $pendingStatus->id)
    //             ->where('attribution_allocation_id', null);

    //         if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
    //             $query->where('region_id', (int) $routeParameter);
    //         }
    //     }

    //     // Add dynamic filtering for the user's region and role
    //     if ($user && $user->hasRole('RO') && $user->region_id) {
    //         $query->whereHas('tvi.district.province.region', function ($subQuery) use ($user) {
    //             $subQuery->where('id', $user->region_id);
    //         });
    //     }

    //     // Debugging: Log the generated query for inspection
    //     Log::info('Generated Query', ['query' => $query->toSql(), 'bindings' => $query->getBindings()]);

    //     return $query;
    // }

}


