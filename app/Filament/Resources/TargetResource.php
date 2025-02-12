<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomPendingTargetExport;
use App\Filament\Resources\TargetResource\Pages;
use App\Models\Abdd;
use App\Models\Allocation;
use App\Models\DeliveryMode;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\SkillPrograms;
use App\Models\Status;
use App\Models\Target;
use App\Models\TargetStatus;
use App\Models\Tvi;
use App\Policies\TargetPolicy;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
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
                        // TextInput::make('abscap_id')
                        //     ->label('Absorptive Capacity ID')
                        //     ->placeholder('Enter an AbsCap ID')
                        //     ->required()
                        //     ->markAsRequired(false)
                        //     ->numeric(),
    
                        Select::make('legislator_id')
                            ->label('Legislator')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function () {
                                return Legislator::where('status_id', 1)
                                    ->whereNull('deleted_at')
                                    ->has('allocation')
                                    ->pluck('name', 'id')
                                    ->toArray() ?: ['no_legislators' => 'No legislators available'];
                            })
                            ->disabled()
                            ->dehydrated()
                            ->validationAttribute('legislator'),

                        Select::make('particular_id')
                            ->label('Particular')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $legislatorId = $get('legislator_id');

                                return $legislatorId
                                    ? self::getParticularOptions($legislatorId)
                                    : ['no_particular' => 'No particulars available'];
                            })
                            ->disabled()
                            ->dehydrated()
                            ->validationAttribute('particular'),

                        Select::make('scholarship_program_id')
                            ->label('Scholarship Program')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $legislatorId = $get('legislator_id');
                                $particularId = $get('particular_id');

                                return $legislatorId
                                    ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                    : ['no_scholarship_program' => 'No scholarship programs available. Select a particular first.'];
                            })
                            ->disabled()
                            ->dehydrated()
                            ->validationAttribute('scholarship program'),

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
                                    : ['no_allocation' => 'No appropriation year available. Select a scholarship program first.'];
                            })
                            ->disabled()
                            ->dehydrated()
                            ->validationAttribute('appropriation year'),

                        Select::make('appropriation_type')
                            ->label('Appropriation Type')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $year = $get('allocation_year');

                                return $year
                                    ? self::getAppropriationTypeOptions($year)
                                    : ['no_allocation' => 'No appropriation type available. Select an appropriation year first.'];
                            })
                            ->disabled()
                            ->dehydrated()
                            ->validationAttribute('appropriation type'),

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
                                        $tvi = Tvi::find($id);

                                        return [$id => "{$tvi->school_id} - {$tvi->name}"];
                                    })
                                    ->toArray() ?: ['no_tvi' => 'No institutions available'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_tvi')
                            ->afterStateUpdated(function (callable $set, $state) {
                                if (!$state) {
                                    $set('qualification_title_id', null);
                                }

                                $set('qualification_title_id', null);
                            })
                            ->reactive()
                            ->live()
                            ->validationAttribute('institution'),

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
                                    : ['no_qualification_title' => 'No qualification titles available. Select a scholarship program first.'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_qualification_title')
                            ->reactive()
                            ->live()
                            ->validationAttribute('qualification title'),

                        Select::make('abdd_id')
                            ->label('ABDD Sector')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(function () {
                                return Abdd::whereNull('deleted_at')
                                    ->pluck('name', 'id')
                                    ->toArray() ?: ['no_abdd' => 'No ABDD sectors available'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_abdd')
                            ->validationAttribute('ABDD sector'),

                        Select::make('delivery_mode_id')
                            ->label('Delivery Mode')
                            ->required()
                            ->markAsRequired(false)
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(function () {
                                $deliveryModes = DeliveryMode::all();

                                return $deliveryModes->isNotEmpty()
                                    ? $deliveryModes->pluck('name', 'id')->toArray()
                                    : ['no_delivery_mode' => 'No delivery modes available'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode')
                            ->validationAttribute('delivery mode'),

                        Select::make('learning_mode_id')
                            ->label('Learning Mode')
                            ->searchable()
                            ->preload()
                            ->native(false)
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
                                    : ['no_learning_modes' => 'No learning modes available for the selected delivery mode'];
                            })
                            ->disableOptionWhen(fn($value) => $value === 'no_learning_modes')
                            ->validationAttribute('learning mode'),

                        TextInput::make('number_of_slots')
                            ->label('Slots')
                            ->placeholder('Enter number of slots')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->currencyMask(precision: 0)
                            ->minLength(1)
                            ->maxLength(2)
                            ->rules(['min: 10', 'max: 25'])
                            ->validationMessages([
                                'min' => 'The number of slots must be at least 10.',
                                'max' => 'The number of slots must not exceed 25.'
                            ])
                            ->validationAttribute('slots'),
                    ];
                } else {
                    return [
                        Repeater::make('targets')
                            ->schema([
                                // TextInput::make('abscap_id')
                                //     ->label('Absorptive Capacity ID')
                                //     ->placeholder('Enter an AbsCap ID')
                                //     ->numeric(),
    
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
                                                $query->where('soft_or_commitment', 'Soft')
                                                    ->where('balance', '>', 0)
                                                    ->whereNull('attributor_id');
                                            })
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_legislators' => 'No legislators available'];
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
                                            ->where('soft_or_commitment', 'Soft')
                                            ->where('balance', '>', 0)
                                            ->whereNull('attributor_id')
                                            ->with('particular', 'scholarship_program')
                                            ->get();

                                        $particularOptions = $allocations->pluck('particular.name', 'particular.id')->toArray();
                                        $scholarshipProgramOptions = $allocations->pluck('scholarship_program.name', 'scholarship_program.id')->toArray();
                                        $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();

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

                                                $currentYear = now()->year;
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
                                    ->live()
                                    ->validationAttribute('legislator'),

                                Select::make('particular_id')
                                    ->label('Particular')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $legislator_id = $get('legislator_id');
                                        $legislatorRecords = Legislator::find($legislator_id);

                                        if ($legislatorRecords) {
                                            $particulars = $legislatorRecords->particular()->with(['subParticular', 'district.province.region'])->get();

                                            if ($particulars->isNotEmpty()) {
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

                                                return $options ?: ['no_particular' => 'No particulars available'];
                                            }
                                        }

                                        return ['no_particular' => 'No particulars available. Select a legislator first.'];
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
                                            ->where('soft_or_commitment', 'Soft')
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
                                    ->live()
                                    ->validationAttribute('particular'),

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
                                            : ['no_scholarship_program' => 'No scholarship programs available. Select a particular first.'];
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
                                    ->live()
                                    ->validationAttribute('scholarship program'),

                                Select::make('allocation_year')
                                    ->label('Appropriation Year')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('legislator_id');
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
                                    ->live()
                                    ->validationAttribute('appropriation year'),

                                Select::make('appropriation_type')
                                    ->label('Appropriation Type')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->native(false)
                                    ->options(function ($get) {
                                        $year = $get('allocation_year');

                                        return $year
                                            ? self::getAppropriationTypeOptions($year)
                                            : ['no_allocation' => 'No appropriation types available. Select an appropriation year first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_allocation')
                                    ->reactive()
                                    ->live()
                                    ->validationAttribute('appropriation type'),

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
                                                $tvi = Tvi::find($id);
                                                return [$id => "{$tvi->school_id} - {$tvi->name}"];
                                            })
                                            ->toArray() ?: ['no_tvi' => 'No institutions available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_tvi')
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if (!$state) {
                                            $set('qualification_title_id', null);
                                        }

                                        $set('qualification_title_id', null);
                                    })
                                    ->reactive()
                                    ->live()
                                    ->validationAttribute('institution'),

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
                                            : ['no_qualification_title' => 'No qualification titles available. Select a scholarship program first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_qualification_title')
                                    ->reactive()
                                    ->live()
                                    ->validationAttribute('qualification title'),

                                Select::make('abdd_id')
                                    ->label('ABDD Sector')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function () {
                                        return Abdd::whereNull('deleted_at')
                                            ->whereNot('name', 'Not Applicable')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_abdd' => 'No ABDD sectors available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_abdd')
                                    ->validationAttribute('ABDD sector'),

                                Select::make('delivery_mode_id')
                                    ->label('Delivery Mode')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->options(function () {
                                        $deliveryModes = DeliveryMode::all();

                                        return $deliveryModes->isNotEmpty()
                                            ? $deliveryModes->pluck('name', 'id')->toArray()
                                            : ['no_delivery_mode' => 'No delivery modes available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode')
                                    ->validationAttribute('delivery mode'),

                                Select::make('learning_mode_id')
                                    ->label('Learning Mode')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
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
                                            : ['no_learning_modes' => 'No learning modes available for the selected delivery mode'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_learning_modes')
                                    ->validationAttribute('learning mode'),

                                TextInput::make('number_of_slots')
                                    ->label('Slots')
                                    ->placeholder('Enter number of slots')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->autocomplete(false)
                                    ->numeric()
                                    ->currencyMask(precision: 0)
                                    ->minLength(1)
                                    ->maxLength(2)
                                    ->rules(['min: 10', 'max: 25'])
                                    ->validationMessages([
                                        'min' => 'The number of slots must be at least 10.',
                                        'max' => 'The number of slots must not exceed 25.'
                                    ])
                                    ->validationAttribute('slots'),
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No targets available')
            ->columns([
                // TextColumn::make('abscap_id')
                //     ->sortable()
                //     ->searchable()
                //     ->toggleable(),

                TextColumn::make('fund_source')
                    ->label('Fund Source')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $particular = $record->allocation->particular;
                        $subParticular = $particular->subParticular;
                        $fundSource = $subParticular ? $subParticular->fundSource : null;

                        return $fundSource ? $fundSource->name : '-';
                    }),

                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Source of Fund')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.legislator.particular.subParticular')
                    ->label('Particular')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;
                        $particulars = $legislator->particular;

                        $particular = $particulars->first();
                        $district = $particular->district;
                        $municipality = $district ? $district->underMunicipality : null;

                        $districtName = $district ? $district->name : '';
                        $provinceName = $district ? $district->province->name : '';
                        $municipalityName = $municipality ? $municipality->name : '';

                        if ($districtName === 'Not Applicable') {
                            if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else {
                                return $particular->subParticular->name ?? '-';
                            }
                        } else {
                            if ($municipality === '') {
                                return "{$particular->subParticular->name} - {$districtName}, {$provinceName}";
                            } else {
                                return "{$particular->subParticular->name} - {$districtName}, {$municipalityName}, {$provinceName}";
                            }
                        }
                    }),

                TextColumn::make('appropriation_type')
                    ->label('Appropriation Type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.year')
                    ->label('Appropriation Year')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.tviClass.name')
                    ->label('Institution Class')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $institutionType = $record->tvi->tviType->name ?? '';
                        $institutionClass = $record->tvi->tviClass->name ?? '';

                        return "{$institutionType} - {$institutionClass}";
                    }),

                TextColumn::make('location')
                    ->label('Address')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => self::getLocationNames($record)),

                TextColumn::make('qualification_title_code')
                    ->label('Qualification Code')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->qualification_title_code ?? '-'),

                TextColumn::make('qualification_title_name')
                    ->label('Qualification Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $qualificationCode = $record->qualification_title_soc_code ?? '';
                        $qualificationName = $record->qualification_title_name ?? '';

                        return "{$qualificationCode} - {$qualificationName}";
                    }),

                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('abdd.name')
                    ->label('ABDD Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.tvet.name')
                    ->label('TVET Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('deliveryMode.name')
                    ->label('Delivery Mode')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('learningMode.name')
                    ->label('Learning Mode')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->learningMode->name ?? '-'),

                TextColumn::make('number_of_slots')
                    ->label('Slots')
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
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter pending target')),
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
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn() => !Auth::user()->hasRole('TESDO')),

                    Action::make('setAsNonCompliant')
                        ->label('Set as Non-compliant')
                        ->url(fn($record) => route('filament.admin.resources.non-compliant-targets.create', ['record' => $record->id]))
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn() => !Auth::user()->hasRole('TESDO')),

                    DeleteAction::make()
                        ->action(function ($record) {
                            $allocation = $record->allocation;
                            $totalAmount = $record->total_amount;

                            $qualificationTitleId = $record->qualification_title_id;
                            $trainingProgramId = QualificationTitle::find($qualificationTitleId)->training_program_id;

                            $provinceId = $record->tvi->district->province_id;
                            $districtId = $record->tvi->district_id;

                            $quali = QualificationTitle::find($qualificationTitleId);
                            $toolkit = $quali->toolkits()->where('year', $allocation->year)->first();

                            $stepId = ScholarshipProgram::where('name', 'STEP')->first();
                            $compliant = TargetStatus::where("desc", "Compliant")->first();

                            $slots = $record->number_of_slots;

                            $totalCostOfToolkit = 0;
                            if ($quali->scholarship_program_id === $stepId->id && $record->target_status_id === $compliant->id) {

                                $toolkit->available_number_of_toolkits += $slots;
                                $toolkit->save();
                            }

                            $active = Status::where('desc', 'Active')->first();
                            $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $active) {
                                    $query->where('province_id', $provinceId)
                                        ->where('district_id', $districtId)
                                        ->where('year', $allocation->year)
                                        ->where('status_id', $active->id);
                                })
                                ->first();

                            if (!$skillPrograms) {
                                $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                    ->whereHas('skillPriority', function ($query) use ($record) {
                                        $query->where('province_id', $record->tvi->district->province_id)
                                            ->where('year', $record->allocation->year);
                                    })
                                    ->first();
                            }

                            $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

                            $skillsPriority->available_slots += $slots;
                            $skillsPriority->save();

                            $allocation->balance += $totalAmount + $totalCostOfToolkit;
                            $allocation->save();

                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Target has been deleted successfully.');
                        })->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete target ')),

                    RestoreAction::make()
                        ->action(function ($record) {
                            $allocation = $record->allocation;
                            $totalAmount = $record->total_amount;

                            $qualificationTitleId = $record->qualification_title_id;
                            $trainingProgramId = QualificationTitle::find($qualificationTitleId)->training_program_id;

                            $provinceId = $record->tvi->district->province_id;
                            $districtId = $record->tvi->district_id;

                            $quali = QualificationTitle::find($qualificationTitleId);
                            $toolkit = $quali->toolkits()->where('year', $allocation->year)->first();

                            $stepId = ScholarshipProgram::where('name', 'STEP')->first();
                            $compliant = TargetStatus::where("desc", "Compliant")->first();

                            $slots = $record->number_of_slots;
                            $totalCostOfToolkit = 0;

                            if ($quali->scholarship_program_id === $stepId->id && $record->target_status_id === $compliant->id) {

                                $toolkit->available_number_of_toolkits -= $slots;
                                $toolkit->save();
                            }

                            $active = Status::where('desc', 'Active')->first();
                            $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $active) {
                                    $query->where('province_id', $provinceId)
                                        ->where('district_id', $districtId)
                                        ->where('year', $allocation->year)
                                        ->where('status_id', $active->id);
                                })
                                ->first();

                            if (!$skillPrograms) {
                                $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                    ->whereHas('skillPriority', function ($query) use ($record) {
                                        $query->where('province_id', $record->tvi->district->province_id)
                                            ->where('year', $record->allocation->year);
                                    })
                                    ->first();
                            }

                            $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

                            if ($skillsPriority->available_slots < $slots) {
                                $message = "Insuffucient Target Benificiaries for the Skill Priority of {$quali->trainingProgram->title} under District {$record->tvi->district->name} in {$record->tvi->district->province->name}.";
                                NotificationHandler::handleValidationException('Something went wrong', $message);
                            }

                            $skillsPriority->available_slots -= $slots;
                            $skillsPriority->save();

                            if ($allocation->balance < $totalAmount + $totalCostOfToolkit) {
                                $message = "Insuffucient Allocation Balance for {$allocation->legislator->name}.";
                                NotificationHandler::handleValidationException('Something went wrong', $message);
                            }

                            $allocation->balance -= $totalAmount + $totalCostOfToolkit;
                            $allocation->save();

                            $record->deleted_at = null;
                            $record->save();

                            NotificationHandler::sendSuccessNotification('Restored', 'Target has been restored successfully.');
                        }),

                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Target has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $allocation = $record->allocation;
                                $totalAmount = $record->total_amount;

                                $qualificationTitleId = $record->qualification_title_id;
                                $trainingProgramId = QualificationTitle::find($qualificationTitleId)->training_program_id;

                                $provinceId = $record->tvi->district->province_id;
                                $districtId = $record->tvi->district_id;

                                $quali = QualificationTitle::find($qualificationTitleId);
                                $toolkit = $quali->toolkits()->where('year', $allocation->year)->first();

                                $stepId = ScholarshipProgram::where('name', 'STEP')->first();
                                $compliant = TargetStatus::where("desc", "Compliant")->first();

                                $slots = $record->number_of_slots;
                                $totalCostOfToolkit = 0;

                                if ($quali->scholarship_program_id === $stepId->id && $record->target_status_id === $compliant->id) {

                                    $toolkit->available_number_of_toolkits += $slots;
                                    $toolkit->save();
                                }

                                $active = Status::where('desc', 'Active')->first();
                                $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                    ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $active) {
                                        $query->where('province_id', $provinceId)
                                            ->where('district_id', $districtId)
                                            ->where('year', $allocation->year)
                                            ->where('status_id', $active->id);
                                    })
                                    ->first();

                                if (!$skillPrograms) {
                                    $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                        ->whereHas('skillPriority', function ($query) use ($record) {
                                            $query->where('province_id', $record->tvi->district->province_id)
                                                ->where('year', $record->allocation->year);
                                        })
                                        ->first();
                                }

                                $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

                                $skillsPriority->available_slots += $slots;
                                $skillsPriority->save();

                                $allocation->balance += $totalAmount + $totalCostOfToolkit;
                                $allocation->save();

                                $record->delete();
                            });

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected target have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete target ')),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $allocation = $record->allocation;
                                $totalAmount = $record->total_amount;

                                $qualificationTitleId = $record->qualification_title_id;
                                $trainingProgramId = QualificationTitle::find($qualificationTitleId)->training_program_id;

                                $provinceId = $record->tvi->district->province_id;
                                $districtId = $record->tvi->district_id;

                                $quali = QualificationTitle::find($qualificationTitleId);
                                $toolkit = $quali->toolkits()->where('year', $allocation->year)->first();

                                $stepId = ScholarshipProgram::where('name', 'STEP')->first();
                                $compliant = TargetStatus::where("desc", "Compliant")->first();

                                $slots = $record->number_of_slots;

                                $totalCostOfToolkit = 0;
                                if ($quali->scholarship_program_id === $stepId->id && $record->target_status_id === $compliant->id) {

                                    $toolkit->available_number_of_toolkits -= $slots;
                                    $toolkit->save();
                                }

                                $active = Status::where('desc', 'Active')->first();
                                $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                    ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $active) {
                                        $query->where('province_id', $provinceId)
                                            ->where('district_id', $districtId)
                                            ->where('year', $allocation->year)
                                            ->where('status_id', $active->id);
                                    })
                                    ->first();

                                if (!$skillPrograms) {
                                    $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                        ->whereHas('skillPriority', function ($query) use ($record) {
                                            $query->where('province_id', $record->tvi->district->province_id)
                                                ->where('year', $record->allocation->year);
                                        })
                                        ->first();
                                }

                                $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

                                if ($skillsPriority->available_slots < $slots) {
                                    $message = "Insuffucient Target Benificiaries for the Skill Priority of {$quali->trainingProgram->title} under District {$record->tvi->district->name} in {$record->tvi->district->province->name}.";
                                    NotificationHandler::handleValidationException('Something went wrong', $message);
                                }

                                $skillsPriority->available_slots -= $slots;
                                $skillsPriority->save();

                                if ($allocation->balance < $totalAmount + $totalCostOfToolkit) {
                                    $message = "Insuffucient Allocation Balance for {$allocation->legislator->name}.";
                                    NotificationHandler::handleValidationException('Something went wrong', $message);
                                }

                                $allocation->balance -= $totalAmount + $totalCostOfToolkit;
                                $allocation->save();

                                $record->deleted_at = null;
                                $record->save();
                            });

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected target have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore target ')),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected targets have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete target ')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomPendingTargetExport::make()
                                ->withColumns([
                                    // Column::make('abscap_id')
                                    //     ->heading('Absorptive Capacity'),

                                    Column::make('fund_source')
                                        ->heading('Fund Source')
                                        ->getStateUsing(function ($record) {
                                            $particular = $record->allocation->particular;
                                            $subParticular = $particular->subParticular;
                                            $fundSource = $subParticular ? $subParticular->fundSource : null;

                                            return $fundSource ? $fundSource->name : '-';
                                        }),

                                    Column::make('allocation.soft_or_commitment')
                                        ->heading('Source of Fund'),

                                    Column::make('allocation.legislator.name')
                                        ->heading('Legislator'),

                                    Column::make('allocation.legislator.particular.subParticular')
                                        ->heading('Particular')
                                        ->getStateUsing(function ($record) {
                                            $legislator = $record->allocation->legislator;
                                            $particulars = $legislator->particular;

                                            $particular = $particulars->first();
                                            $district = $particular->district;
                                            $municipality = $district ? $district->underMunicipality : null;

                                            $districtName = $district ? $district->name : '';
                                            $provinceName = $district ? $district->province->name : '';
                                            $municipalityName = $municipality ? $municipality->name : '';

                                            if ($districtName === 'Not Applicable') {
                                                if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                                    return "{$particular->subParticular->name} - {$particular->partylist->name}";
                                                } else {
                                                    return $particular->subParticular->name ?? '-';
                                                }
                                            } else {
                                                if ($municipality === '') {
                                                    return "{$particular->subParticular->name} - {$districtName}, {$provinceName}";
                                                } else {
                                                    return "{$particular->subParticular->name} - {$districtName}, {$municipalityName}, {$provinceName}";
                                                }
                                            }
                                        }),

                                    Column::make('appropriation_type')
                                        ->heading('Appropriation Type'),

                                    Column::make('allocation.year')
                                        ->heading('Appropriation Year'),


                                    Column::make('tvi.name')
                                        ->heading('Institution'),

                                    Column::make('tvi.tviType.name')
                                        ->heading('Institution Type'),

                                    Column::make('tvi.tviClass.name')
                                        ->heading('Institution Class'),

                                    Column::make('district.name')
                                        ->heading('District'),

                                    Column::make('municipality.name')
                                        ->heading('Municipality'),

                                    Column::make('tvi.district.province.name')
                                        ->heading('Province'),

                                    Column::make('tvi.district.province.region.name')
                                        ->heading('Region'),


                                    Column::make('qualification_title_code')
                                        ->heading('Qualification Code')
                                        ->getStateUsing(fn($record) => $record->qualification_title_code ?? '-'),

                                    Column::make('qualification_title_name')
                                        ->heading('Qualification Title')
                                        ->formatStateUsing(function ($state, $record) {
                                            $qualificationCode = $record->qualification_title_soc_code ?? '';
                                            $qualificationName = $record->qualification_title_name ?? '';

                                            return "{$qualificationCode} - {$qualificationName}";
                                        }),

                                    Column::make('allocation.scholarship_program.name')
                                        ->heading('Scholarship Program'),

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

                                    Column::make('number_of_slots')
                                        ->heading('Slots'),

                                    Column::make('training_cost_per_slot')
                                        ->heading('Training Cost')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_training_cost_pcc'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('cost_of_toolkit_per_slot')
                                        ->heading('Cost of Toolkit')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_cost_of_toolkit_pcc'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('training_support_fund_per_slot')
                                        ->heading('Training Support Fund')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_training_support_fund'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('assessment_fee_per_slot')
                                        ->heading('Assessment Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_assessment_fee'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('entrepreneurship_fee_per_slot')
                                        ->heading('Entrepreneurship Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_entrepreneurship_fee'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('new_normal_assistance_per_slot')
                                        ->heading('New Normal Assistance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_new_normal_assistance'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('accident_insurance_per_slot')
                                        ->heading('Accident Insurance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_accident_insurance'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('book_allowance_per_slot')
                                        ->heading('Book Allowance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_book_allowance'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('uniform_allowance_per_slot')
                                        ->heading('Uniform Allowance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_uniform_allowance'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('misc_fee_per_slot')
                                        ->heading('Miscellaneous Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_misc_fee'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_amount_per_slot')
                                        ->heading('PCC')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_amount'))
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_training_cost_pcc')
                                        ->heading('Total Training Cost')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_cost_of_toolkit_pcc')
                                        ->heading('Total Cost of Toolkit')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_training_support_fund')
                                        ->heading('Total Training Support Fund')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_assessment_fee')
                                        ->heading('Total Assessment Fee')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_entrepreneurship_fee')
                                        ->heading('Total Entrepreneurship Fee')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_new_normal_assisstance')
                                        ->heading('Total New Normal Assistance')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_accident_insurance')
                                        ->heading('Total Accident Insurance')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_book_allowance')
                                        ->heading('Total Book Allowance')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),
                                    Column::make('total_uniform_allowance')
                                        ->heading('Total Uniform Allowance')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_misc_fee')
                                        ->heading('Total Miscellaneous Fee')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('total_amount')
                                        ->heading('Total PCC')
                                        ->formatStateUsing(function ($state) {
                                            $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
                                            return $formatter->formatCurrency($state, 'PHP');
                                        }),

                                    Column::make('targetStatus.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - pending_target_export')
                        ]),
                ]),
            ]);
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
                    if ($particular->district->underMunicipality) {
                        return [$particular->id => $particular->subParticular->name . " - " . $particular->district->name . ', ' . $particular->district->underMunicipality->name];
                    } else {
                        return [$particular->id => $particular->subParticular->name . " - " . $particular->district->name . ', ' . $particular->district->province->name];
                    }
                }
            })
            ->toArray() ?: ['no_particular' => 'No particulars available'];
    }

    protected static function getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
    {
        $yearNow = date('Y');

        return Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->where('year', '>=', $yearNow - 1)
            ->pluck('year', 'year')
            ->toArray() ?: ['no_allocation' => 'No appropriation year available'];
    }

    protected static function getAppropriationTypeOptions($year)
    {
        $yearNow = date('Y');

        if ($year == $yearNow) {
            return ["Current" => "Current"];
        } elseif ($year == $yearNow - 1) {
            return ["Continuing" => "Continuing"];
        } else {
            return ['no_allocation' => 'No appropriation types available'];
        }
    }

    protected static function getLocationNames($record): string
    {
        $tvi = $record->tvi;

        if ($tvi) {
            $districtName = $tvi->district->name ?? '';
            $provinceName = $tvi->district->province->name ?? '';
            $regionName = $tvi->district->province->region->name ?? '';
            $municipalityName = $tvi->district->underMunicipality->name ?? '';

            if ($regionName === 'NCR') {
                return "{$districtName}, {$municipalityName}, {$provinceName}, {$regionName}";
            } else {
                return "{$municipalityName}, {$districtName}, {$provinceName}, {$regionName}";
            }
        }

        return 'Location information not available';
    }

    protected static function getQualificationTitles($scholarshipProgramId, $tviId, $year)
    {
        $tvi = Tvi::with(['district.province'])->find($tviId);

        if (!$tvi || !$tvi->district || !$tvi->district->province) {
            return ['no_qualification_title' => 'No qualification titles available. Select an institution first.'];
        }

        $provinceId = $tvi->district->province->id;

        $institutionPrograms = $tvi->trainingPrograms()
            ->pluck('training_programs.id')
            ->toArray();

        if (empty($institutionPrograms)) {
            return ['no_qualification_title' => 'No qualification titles available for the selected institution'];
        }

        $schoPro = ScholarshipProgram::where('id', $scholarshipProgramId)->first();

        $scholarshipPrograms = ScholarshipProgram::where('code', $schoPro->code)
            ->pluck('id')
            ->toArray();

        $qualificationTitlesQuery = QualificationTitle::whereIn('scholarship_program_id', $scholarshipPrograms)
            ->where('status_id', 1)
            ->where('soc', 1)
            ->whereNull('deleted_at')
            ->with('trainingProgram')
            ->get();

        if ($qualificationTitlesQuery->isEmpty()) {
            return ['no_qualification_title' => 'No qualification titles available for the selected scholarship program'];
        }

        $skillPriorities = SkillPriority::where('province_id', $provinceId)
            ->where('available_slots', '>=', 10)
            ->where('year', $year)
            ->with('trainingProgram')
            ->get();

        if ($skillPriorities->isEmpty()) {
            return ['no_qualification_title' => 'No qualification titles available. No skill priorities with sufficient target beneficiaries in the selected province.'];
        }

        $qualifiedProgramIds = $skillPriorities->pluck('trainingProgram.*.id')
            ->flatten()
            ->unique()
            ->toArray();

        $qualificationTitles = $qualificationTitlesQuery->filter(function ($qualification) use ($institutionPrograms, $qualifiedProgramIds) {
            return in_array($qualification->training_program_id, $institutionPrograms) && in_array($qualification->training_program_id, $qualifiedProgramIds);
        })->mapWithKeys(function ($qualification) {
            return [$qualification->id => "{$qualification->trainingProgram->soc_code} - {$qualification->trainingProgram->title} ({$qualification->scholarshipProgram->name})"];
        })->toArray();

        return !empty($qualificationTitles) ? $qualificationTitles : ['' => 'No Qualification Titles available'];
    }

    private function getSkillPriority(int $trainingProgramId, $districtId, int $provinceId, int $appropriationYear)
    {
        $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
            ->whereHas('skillPriority', function ($query) use ($districtId, $provinceId, $appropriationYear) {
                $query->where('province_id', $provinceId)
                    ->where('district_id', $districtId)
                    ->where('year', $appropriationYear);
            })
            ->first();

        if (!$skillPrograms) {
            $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                ->whereHas('skillPriority', function ($query) use ($provinceId, $appropriationYear) {
                    $query->where('province_id', $provinceId)
                        ->where('year', $appropriationYear);
                })
                ->first();
        }

        $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

        return $skillsPriority;
    }

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId)
    {
        return ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
            $query->where('legislator_id', $legislatorId)
                ->where('particular_id', $particularId);
        })
            ->pluck('name', 'id')
            ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');
        $pendingStatus = TargetStatus::where('desc', 'Pending')->first();

        if ($pendingStatus) {
            $query->withoutGlobalScopes([SoftDeletingScope::class])
                ->where('target_status_id', '=', $pendingStatus->id)
                ->whereHas('qualification_title', function ($subQuery) {
                    $subQuery->where('soc', 1);
                })
                ->whereHas('allocation', function ($subQuery) {
                    $subQuery->whereNull('attributor_id')
                        ->where('soft_or_commitment', 'Soft');
                });

            if (!request()->is('*/edit') && $routeParameter && filter_var($routeParameter, FILTER_VALIDATE_INT)) {
                $query->where('region_id', (int) $routeParameter);
            }
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user && app(TargetPolicy::class)->viewActionable($user);
    }

    public static function canUpdate($record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user && app(TargetPolicy::class)->update($user, $record);
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
}
