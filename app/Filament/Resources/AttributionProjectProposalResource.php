<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributionProjectProposalResource\Pages;
use App\Filament\Resources\AttributionProjectProposalResource\RelationManagers;
use App\Models\Abdd;
use App\Models\Allocation;
use App\Models\DeliveryMode;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\ProvinceAbdd;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SubParticular;
use App\Models\Target;
use App\Models\TargetStatus;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttributionProjectProposalResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Attribution Project Proposal";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(function ($record) {
                if ($record) {
                    return [
                        Fieldset::make('Sender')
                            ->schema([
                                Select::make('attribution_sender')
                                    ->label('Attributor')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function () {
                                        $houseSpeakerIds = SubParticular::whereIn('name', ['House Speaker', 'House Speaker (LAKAS)'])
                                            ->pluck('id');

                                        return Legislator::where('status_id', 1)
                                            ->whereNull('deleted_at')
                                            ->has('allocation')
                                            ->whereHas('particular', function ($query) use ($houseSpeakerIds) {
                                                $query->whereIn('sub_particular_id', $houseSpeakerIds);
                                            })
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_legislator' => 'No attributors available'];
                                    })
                                    ->disabled()
                                    ->dehydrated(),

                                Select::make('attribution_sender_particular')
                                    ->label('Particular')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('attribution_sender');

                                        if ($legislatorId) {
                                            return Particular::whereHas('legislator', function ($query) use ($legislatorId) {
                                                $query->where('legislator_particular.legislator_id', $legislatorId);
                                            })
                                                ->with('subParticular')
                                                ->get()
                                                ->pluck('subParticular.name', 'id')
                                                ->toArray() ?: ['no_particular' => 'No particulars available'];
                                        }

                                        return ['no_particular' => 'No particulars available. Select an attributor first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated(),

                                Select::make('attribution_scholarship_program')
                                    ->label('Scholarship Program')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('attribution_sender');
                                        $particularId = $get('attribution_sender_particular');

                                        return $legislatorId
                                            ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                            : ['no_scholarship_program' => 'No scholarship programs available. Select a particular first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated(),

                                Select::make('allocation_year')
                                    ->label('Appropriation Year')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('attribution_sender');
                                        $particularId = $get('attribution_sender_particular');
                                        $scholarshipProgramId = $get('attribution_scholarship_program');

                                        return $legislatorId
                                            ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                            : ['no_allocation' => 'No appropriation years available. Select a scholarship program first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated(),

                                Select::make('attribution_appropriation_type')
                                    ->label('Appropriation Type')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $year = $get('allocation_year');

                                        return $year
                                            ? self::getAppropriationTypeOptions($year)
                                            : ['no_allocation' => 'No appropriation types available. Select an appropriation year first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(5),

                        Fieldset::make('Receiver')
                            ->schema([
                                TextInput::make('abscap_id')
                                    ->label('Absorbative Capacity ID')
                                    ->placeholder('Enter AbsCap ID')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->autocomplete(false)
                                    ->integer(),

                                Select::make('attribution_receiver')
                                    ->label('Legislator')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $attributor_id = $get('attribution_sender');

                                        return Legislator::where('status_id', 1)
                                            ->whereNot('id', $attributor_id)
                                            ->whereNull('deleted_at')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_legislator' => 'No legislators available'];
                                    })
                                    ->disabled()
                                    ->dehydrated(),

                                Select::make('attribution_receiver_particular')
                                    ->label('Particular')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get, $set) {
                                        $legislatorId = $get('attribution_receiver');

                                        if ($legislatorId) {
                                            $particulars = Particular::whereHas('legislator', function ($query) use ($legislatorId) {
                                                $query->where('legislator_particular.legislator_id', $legislatorId);
                                            })
                                                ->with('subParticular')
                                                ->get();

                                            $particularOptions = $particulars->mapWithKeys(function ($particular) {
                                                if ($particular->subParticular) {
                                                    if ($particular->subParticular->name === 'Party-list') {
                                                        $name = $particular->partylist->name;
                                                    } elseif ($particular->subParticular->name === 'District') {
                                                        $name = $particular->district->name . ' - ' . $particular->district->province->name . ', ' . $particular->district->province->region->name;
                                                    } elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                                                        $name = $particular->subParticular->name . ' - ' . $particular->district->province->region->name;
                                                    } else {
                                                        $name = $particular->subParticular->name;
                                                    }
                                                } else {
                                                    $name = $particular->name;
                                                }

                                                return [$particular->id => $name];
                                            })->toArray();

                                            if (count($particularOptions) === 1) {
                                                $defaultParticularId = key($particularOptions);
                                                $set('attribution_receiver_particular', $defaultParticularId);
                                            }

                                            return $particularOptions ?: ['no_particular' => 'No particulars available'];
                                        }

                                        return ['no_particular' => 'No particulars available. Select a legislator first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->reactive(),

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
                                        $scholarshipProgramId = $get('attribution_scholarship_program');
                                        $tviId = $get('tvi_id');
                                        $year = $get('allocation_year');

                                        return $scholarshipProgramId
                                            ? self::getQualificationTitles($scholarshipProgramId, $tviId, $year)
                                            : ['no_qualification_title' => 'No qualification titles available. Select a scholarship program first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

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
                                    ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode'),

                                Select::make('learning_mode_id')
                                    ->label('Learning Mode')
                                    ->required()
                                    ->markAsRequired(false)
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
                                            : ['no_learning_modes' => 'No learning modes available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_learning_modes'),

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
                                    ->disableOptionWhen(fn($value) => $value === 'no_abdd'),

                                TextInput::make('number_of_slots')
                                    ->label('Slots')
                                    ->placeholder('Enter number of slots')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->autocomplete(false)
                                    ->integer()
                                    ->rules(['min: 10', 'max: 25'])
                                    ->validationAttribute('Number of Slots')
                                    ->validationMessages([
                                        'min' => 'The number of slots must be at least 10.',
                                        'max' => 'The number of slots must not exceed 25.'
                                    ]),

                                TextInput::make('per_capita_cost')
                                    ->label('Per Capita Cost')
                                    ->placeholder('Enter per capita cost')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->autocomplete(false)
                                    ->prefix('₱')
                                    ->numeric()
                            ])
                            ->columns(5)
                    ];
                } else {
                    return [
                        Repeater::make('targets')
                            ->schema([
                                Fieldset::make('Sender')
                                    ->schema([
                                        Select::make('attribution_sender')
                                            ->label('Attributor')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->options(function () {
                                                $houseSpeakerIds = SubParticular::whereNotIn('name', ['District', 'Party-list', 'Senator'])
                                                    ->pluck('id');

                                                return Legislator::where('status_id', 1)
                                                    ->whereNull('deleted_at')
                                                    ->whereHas('attributions', function ($query) {
                                                        $query->where('soft_or_commitment', 'Commitment')
                                                            ->whereNotNull('attributor_id');
                                                    })
                                                    ->whereHas('particular', function ($query) use ($houseSpeakerIds) {
                                                        $query->whereIn('sub_particular_id', $houseSpeakerIds);
                                                    })
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_legislator' => 'No attributors available'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('attribution_sender_particular', null);
                                                    $set('attribution_scholarship_program', null);
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

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
                                                    $set('attribution_sender_particular', key($particularOptions));
                                                } else {
                                                    $set('attribution_sender_particular', null);
                                                }

                                                if (count($scholarshipProgramOptions) === 1) {
                                                    $set('attribution_scholarship_program', key($scholarshipProgramOptions));
                                                } else {
                                                    $set('attribution_scholarship_program', null);
                                                }

                                                $particularId = $particularOptions ? key($particularOptions) : null;
                                                $scholarshipProgramId = $scholarshipProgramOptions ? key($scholarshipProgramOptions) : null;

                                                if ($particularId && $scholarshipProgramId) {
                                                    if (count($allocations) === 1) {
                                                        $set('allocation_year', key($appropriationYearOptions));

                                                        if (key($appropriationYearOptions) == $currentYear) {
                                                            $set('attribution_appropriation_type', 'Current');
                                                        }
                                                    } else {
                                                        $set('allocation_year', null);
                                                        $set('attribution_appropriation_type', null);
                                                    }
                                                } else {
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live(),

                                            Select::make('attribution_sender_particular')
                                            ->label('Particular')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislatorId = $get('attribution_sender');

                                                if ($legislatorId) {
                                                    return Particular::whereHas('legislator', function ($query) use ($legislatorId) {
                                                        $query->where('legislator_particular.legislator_id', $legislatorId);
                                                    })
                                                        ->with('subParticular')
                                                        ->get()
                                                        ->pluck('subParticular.name', 'id')
                                                        ->toArray() ?: ['no_particular' => 'No particulars available'];
                                                }

                                                return ['no_particular' => 'No particulars available. Select an attributor first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_particular')
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if (!$state) {
                                                    $set('attribution_scholarship_program', null);
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

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
                                                    $set('attribution_scholarship_program', key($scholarshipProgramOptions));
                                                    $set('allocation_year', key($appropriationYearOptions));

                                                    if (key($appropriationYearOptions) == $currentYear) {
                                                        $set('attribution_appropriation_type', 'Current');
                                                    }
                                                } else {
                                                    $set('attribution_scholarship_program', null);
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live(),

                                        Select::make('attribution_scholarship_program')
                                            ->label('Scholarship Program')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislatorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');

                                                return $legislatorId
                                                    ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                                    : ['no_scholarship_program' => 'No scholarship programs available. Select a particular first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if (!$state) {
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

                                                    return;
                                                }

                                                $legislator_id = $get('legislator_id');
                                                $particular_id = $get('attribution_sender_particular');
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
                                                        $set('attribution_appropriation_type', 'Current');
                                                    }
                                                } else {
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live(),

                                        Select::make('allocation_year')
                                            ->label('Appropriation Year')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislatorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');
                                                $scholarshipProgramId = $get('attribution_scholarship_program');

                                                return $legislatorId
                                                    ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                                    : ['no_allocation' => 'No appropriation years available. Select a scholarship program first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_allocation')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $set('attribution_appropriation_type', null);

                                                $appropriationType = self::getAppropriationTypeOptions($state);

                                                $set('attribution_appropriation_type', $appropriationType);

                                                if (count($appropriationType) === 1) {
                                                    $set('attribution_appropriation_type', key($appropriationType));
                                                }
                                            })
                                            ->reactive()
                                            ->live(),

                                        Select::make('attribution_appropriation_type')
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
                                            ->live(),
                                    ])
                                    ->columns(5),

                                Fieldset::make('Receiver')
                                    ->schema([
                                        TextInput::make('abscap_id')
                                            ->label('Absorbative Capacity ID')
                                            ->placeholder('Enter AbsCap ID')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->autocomplete(false)
                                            ->integer(),

                                        Select::make('attribution_receiver')
                                            ->label('Legislator')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $attributor_id = $get('attribution_sender');

                                                return Legislator::where('status_id', 1)
                                                    ->whereNot('id', $attributor_id)
                                                    ->whereNull('deleted_at')
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_legislator' => 'No legislators available'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('attribution_receiver_particular', null);

                                                    return;
                                                }

                                                $particulars = Legislator::find($state)->particular;

                                                $particularOptions = $particulars->pluck('name', 'id')->toArray();

                                                if (count($particularOptions) === 1) {
                                                    $set('attribution_receiver_particular', key($particularOptions));
                                                } else {
                                                    $set('attribution_receiver_particular', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live(),

                                        Select::make('attribution_receiver_particular')
                                            ->label('Particular')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->options(function ($get, $set) {
                                                $legislatorId = $get('attribution_receiver');

                                                if ($legislatorId) {
                                                    $particulars = Particular::whereHas('legislator', function ($query) use ($legislatorId) {
                                                        $query->where('legislator_particular.legislator_id', $legislatorId);
                                                    })
                                                        ->with('subParticular')
                                                        ->get();

                                                    $particularOptions = $particulars->mapWithKeys(function ($particular) {
                                                        if ($particular->subParticular) {
                                                            if ($particular->subParticular->name === 'Party-list') {
                                                                $name = $particular->partylist->name;
                                                            } elseif ($particular->subParticular->name === 'District') {
                                                                $name = $particular->district->name . ' - ' . $particular->district->province->name . ', ' . $particular->district->province->region->name;
                                                            } elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                                                                $name = $particular->subParticular->name . ' - ' . $particular->district->province->region->name;
                                                            } else {
                                                                $name = $particular->subParticular->name;
                                                            }
                                                        } else {
                                                            $name = $particular->name;
                                                        }

                                                        return [$particular->id => $name];
                                                    })->toArray();

                                                    if (count($particularOptions) === 1) {
                                                        $defaultParticularId = key($particularOptions);
                                                        $set('attribution_receiver_particular', $defaultParticularId);
                                                    }

                                                    return $particularOptions ?: ['no_particular' => 'No particulars available'];
                                                }

                                                return ['no_particular' => 'No particulars available. Select a legislator first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_particular')
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
                                                $scholarshipProgramId = $get('attribution_scholarship_program');
                                                $tviId = $get('tvi_id');
                                                $year = $get('allocation_year');

                                                return $scholarshipProgramId
                                                    ? self::getQualificationTitles($scholarshipProgramId, $tviId, $year)
                                                    : ['no_qualification_title' => 'No qualification titles available. Select a scholarship program first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

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
                                            ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode'),

                                        Select::make('learning_mode_id')
                                            ->label('Learning Mode')
                                            ->required()
                                            ->markAsRequired(false)
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
                                                    : ['no_learning_modes' => 'No learning modes available'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_learning_modes'),

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
                                            ->dehydrated(),

                                        TextInput::make('number_of_slots')
                                            ->label('Slots')
                                            ->placeholder('Enter number of slots')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->autocomplete(false)
                                            ->integer()
                                            ->rules(['min: 10', 'max: 25'])
                                            ->validationAttribute('Number of Slots')
                                            ->validationMessages([
                                                'min' => 'The number of slots must be at least 10.',
                                                'max' => 'The number of slots must not exceed 25.'
                                            ]),

                                        TextInput::make('per_capita_cost')
                                            ->label('Per Capita Cost')
                                            ->placeholder('Enter per capita cost')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->autocomplete(false)
                                            ->prefix('₱')
                                            ->numeric()
                                    ])
                                    ->columns(3)
                            ])
                            ->maxItems(100)
                            ->columnSpanFull()
                            ->addActionLabel('+')
                            ->cloneable(),
                    ];
                }
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No attribution targets available')
            ->columns([
                TextColumn::make('fund_source')
                    ->label('Fund Source')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No legislators available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No particulars available';
                        }

                        $particular = $record->allocation->particular;
                        $subParticular = $particular->subParticular;
                        $fundSource = $subParticular ? $subParticular->fundSource : null;

                        return $fundSource ? $fundSource->name : 'No fund sources available';
                    }),

                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Source of Fund')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.attributor.name')
                    ->label('Attributor')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $attributor = $record->allocation->attributor;

                        return $attributor ? $attributor->name : '-';
                    }),

                TextColumn::make('attributionAllocation.legislator.particular.subParticular')
                    ->label('Attributor Particular')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $particular = $record->allocation->attributorParticular;

                        if (!$particular) {
                            return '-';
                        }

                        $district = $particular->district;
                        $districtName = $district ? $district->name : 'Unknown District';

                        if ($districtName === 'Not Applicable') {
                            if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else {
                                return $particular->subParticular->name ?? 'Unknown Particular Type';
                            }
                        } else {
                                if ($particular->district->underMunicipality) {
                                    return "{$particular->subParticular->name} - {$districtName}, {$district->underMunicipality->name}, {$district->province->name}";
                                }
                                else {
                                    return "{$particular->subParticular->name} - {$districtName}, {$district->province->name}";
                                }
                        }
                    }),

                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.legislator.particular.subParticular')
                    ->label('Particular')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $particular = $record->allocation->particular;

                        if (!$particular) {
                            return '-';
                        }

                        $district = $particular->district;
                        $districtName = $district ? $district->name : 'Unknown District';

                        if ($districtName === 'Not Applicable') {
                            if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else {
                                return $particular->subParticular->name ?? 'Unknown Particular Type';
                            }
                        } else {
                                if ($particular->district->underMunicipality) {
                                    return "{$particular->subParticular->name} - {$districtName}, {$district->underMunicipality->name}, {$district->province->name}";
                                }
                                else {
                                    return "{$particular->subParticular->name} - {$districtName}, {$district->province->name}";
                                }
                        }
                    }),

                TextColumn::make('appropriation_type')
                    ->label('Appropriation Type')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.year')
                    ->label('Appropriation Year')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn($state) => preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($state))),

                TextColumn::make('location')
                    ->label('Administrative Area')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => self::getLocationNames($record)),

                TextColumn::make('tvi.tviClass.tviType.name')
                    ->label('Institution Class')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $institutionType = $record->tvi->tviClass->tviType->name ?? '';
                        $institutionClass = $record->tvi->tviClass->name ?? '';

                        return "{$institutionType} - {$institutionClass}";
                    }),

                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title_name')
                    ->label('Qualification Title')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $qualificationCode = $record->qualification_title_soc_code ?? '';
                        $qualificationName = $record->qualification_title_name ?? '';

                        if ($qualificationName) {
                            $qualificationName = ucwords($qualificationName);

                            if (preg_match('/\bNC\s+[I]{1,3}\b/i', $qualificationName)) {
                                $qualificationName = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                    return 'NC ' . strtoupper($matches[1]);
                                }, $qualificationName);
                            }
                        }

                        return "{$qualificationCode} - {$qualificationName}";
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
                    ->toggleable(),
            ])
            ->filters([
                //
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
                                    Column::make('attributionAllocation.legislator.name')
                                        ->heading('Attribution Sender'),
                                    Column::make('attributionAllocation.legislator.particular.subParticular')
                                        ->heading('Attributor Particular')
                                        ->getStateUsing(function ($record) {
                                            $legislator = $record->attributionAllocation->legislator;

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
                                    Column::make('allocation.legislator.name')
                                        ->heading('Legislator'),
                                    Column::make('attributionAllocation.soft_or_commitment')
                                        ->heading('Soft or Commitment'),
                                    Column::make('appropriation_type')
                                        ->heading('Appropriation Type'),
                                    Column::make('attributionAllocation.year')
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
                                        ->heading('Institution Class(A)'),
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
                                ->withFilename(date('m-d-Y') . ' - Project Proposals')
                        ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributionProjectProposals::route('/'),
            'create' => Pages\CreateAttributionProjectProposal::route('/create'),
            'edit' => Pages\EditAttributionProjectProposal::route('/{record}/edit'),
        ];
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
                    $subQuery->where('soc', 0); 
                })
                ->whereHas('allocation', function ($subQuery) {
                    $subQuery->whereNotNull('attributor_id')
                        ->where('soft_or_commitment', 'Commitment');
                });

            if (!request()->is('*/edit') && $routeParameter && filter_var($routeParameter, FILTER_VALIDATE_INT)) {
                $query->where('region_id', (int) $routeParameter);
            }
        }

        return $query;
    }

    protected static function getLocationNames($record): string
    {
        $tvi = $record->tvi;

        if ($tvi) {
            $districtName = $tvi->district->name ?? 'Unknown District';
            $provinceName = $tvi->district->province->name ?? 'Unknown Province';
            $regionName = $tvi->district->province->region->name ?? 'Unknown Region';
            $municipalityName = $tvi->district->underMunicipality->name ?? 'Unknown Municipality';

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
                ->where('soc', 0)
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

}


