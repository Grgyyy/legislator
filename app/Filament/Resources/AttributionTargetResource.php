<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomAttributionTargetExport;
use App\Filament\Resources\AttributionTargetResource\Pages;
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
use App\Models\SubParticular;
use App\Models\Target;
use App\Models\TargetComment;
use App\Models\TargetStatus;
use App\Models\Tvi;
use App\Policies\TargetPolicy;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class AttributionTargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Attribution Targets";

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 2;

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
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_legislator' => 'No attributors available'];
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->validationAttribute('attributor'),

                                Select::make('attribution_sender_particular')
                                    ->label('Particular')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('attribution_sender');

                                        if ($legislatorId) {
                                            $allocation = Allocation::whereHas('particular')
                                                ->where('attributor_id', $legislatorId)
                                                ->with('attributorParticular.subParticular')
                                                ->get();

                                            return $allocation->mapWithKeys(function ($allocation) {
                                                $particular = $allocation->attributorParticular;
                                                $subParticular = $particular->subParticular->name ?? '';
                                                $formattedName = '';

                                                if ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
                                                    $regionName = $particular->district->province->region->name ?? '';
                                                    $formattedName = "{$subParticular} - {$regionName}";
                                                } else {
                                                    $formattedName = $subParticular;
                                                }

                                                return [$particular->id => $formattedName];
                                            })->toArray() ?: ['no_particular' => 'No particulars available'];
                                        }

                                        return ['no_particular' => 'No particulars available. Select an attributor first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->validationAttribute('particular'),

                                Select::make('attribution_scholarship_program')
                                    ->label('Scholarship Program')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $legislatorId = $get('attribution_sender');
                                        $particularId = $get('attribution_sender_particular');

                                        if ($legislatorId) {
                                            return ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
                                                $query->where('attributor_id', $legislatorId)
                                                    ->where('attributor_particular_id', $particularId);
                                            })
                                                ->pluck('name', 'id')
                                                ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                                        }

                                        return ['no_scholarship_program' => 'No scholarship programs available. Select a particular first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->validationAttribute('scholarship program'),
                            ])
                            ->columns(3),

                        Fieldset::make('Receiver')
                            ->schema([
                                Select::make('attribution_receiver')
                                    ->label('Legislator')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $legislator = $get('attribution_sender');

                                        if ($legislator) {
                                            $legislatorId = $get('attribution_sender');
                                            $particularId = $get('attribution_sender_particular');
                                            $scholarshipProgramId = $get('attribution_scholarship_program');

                                            $allocations = Allocation::where('attributor_id', $legislatorId)
                                                ->where('attributor_particular_id', $particularId)
                                                ->where('scholarship_program_id', $scholarshipProgramId)
                                                ->with('legislator')
                                                ->get()
                                                ->sortBy('name')
                                                ->pluck('legislator.name', 'legislator.id')
                                                ->toArray();

                                            return $allocations ?? ['no_legislator' => 'No legislators available'];
                                        }

                                        return ['no_legislator' => 'No legislators available. Complete sender fields first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->validationAttribute('legislator'),

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
                                                        $name = $particular->subParticular->name . '-' . $particular->partylist->name;
                                                    } elseif ($particular->subParticular->name === 'District') {
                                                        if ($particular->district->underMunicipality) {
                                                            $name = $particular->subParticular->name . ' - ' . $particular->district->name . ', ' . $particular->district->underMunicipality->name . ', ' . $particular->district->province->name;
                                                        } else {
                                                            $name = $particular->subParticular->name . ' - ' . $particular->district->name . ', ' . $particular->district->province->name;
                                                        }
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

                                            return $particularOptions ?: ['no_particular' => 'No particulars available'];
                                        }

                                        return ['no_particular' => 'No particulars available. Select a legislator first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->validationAttribute('particular'),

                                Select::make('allocation_year')
                                    ->label('Appropriation Year')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->options(function ($get) {
                                        $attributorId = $get('attribution_sender');
                                        $legislatorId = $get('attribution_receiver');
                                        $attributorParticularId = $get('attribution_sender_particular');
                                        $particularId = $get('attribution_receiver_particular');
                                        $scholarshipProgramId = $get('attribution_scholarship_program');

                                        return $legislatorId
                                            ? self::getAllocationYear($attributorId, $legislatorId, $attributorParticularId, $particularId, $scholarshipProgramId)
                                            : ['no_allocation' => 'No appropriation year available. Select a particular first.'];
                                    })
                                    ->disabled()
                                    ->dehydrated()
                                    ->validationAttribute('appropriation year'),

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
                                    ->dehydrated()
                                    ->validationAttribute('appropriation type'),

                                Select::make('tvi_id')
                                    ->label('Institution')
                                    ->relationship('tvi', 'name')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->options(function () {
                                        return TVI::whereNot('name', 'Not Applicable')
                                            ->has('trainingPrograms')
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($tvi) {
                                                $schoolId = $tvi->school_id;
                                                $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;

                                                return [$tvi->id => $formattedName];
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
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $scholarshipProgramId = $get('attribution_scholarship_program');
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
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_abdd' => 'No ABDD sectors available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_abdd')
                                    ->validationAttribute('ABDD sector'),

                                Select::make('delivery_mode_id')
                                    ->label('Delivery Mode')
                                    ->required()
                                    ->markAsRequired(false)
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->options(function () {
                                        return DeliveryMode::whereNull('deleted_at')
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_delivery_mode' => 'No delivery modes available'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode')
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if (!$state) {
                                            $set('learning_mode_id', null);
                                        }

                                        $set('learning_mode_id', null);
                                    })
                                    ->reactive()
                                    ->live()
                                    ->validationAttribute('delivery mode'),

                                Select::make('learning_mode_id')
                                    ->label('Learning Mode')
                                    ->preload()
                                    ->searchable()
                                    ->native(false)
                                    ->options(function ($get) {
                                        $deliveryModeId = $get('delivery_mode_id');

                                        if ($deliveryModeId) {
                                            return DeliveryMode::find($deliveryModeId)?->learningMode
                                                ->sortBy('name')
                                                ->pluck('name', 'id')
                                                ->toArray() ?: ['no_learning_modes' => 'No learning modes available'];
                                        }

                                        return ['no_learning_modes' => 'No learning modes available. Select a delivery mode first.'];
                                    })
                                    ->disableOptionWhen(fn($value) => $value === 'no_learning_modes')
                                    ->reactive()
                                    ->live()
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
                            ->columns(3)
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
                                            ->preload()
                                            ->searchable()
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
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_legislator' => 'No attributors available'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('attribution_sender_particular', null);
                                                    $set('attribution_scholarship_program', null);
                                                    $set('attribution_receiver', null);
                                                    $set('attribution_receiver_particular', null);
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

                                                    return;
                                                }

                                                $allocations = Allocation::where('attributor_id', $state)
                                                    ->with('particular', 'scholarship_program')
                                                    ->get();

                                                $AttributorParticularOptions = $allocations->pluck('attributorParticular.name', 'attributorParticular.id')->toArray();
                                                $legislatorOptions = $allocations->pluck('legislator.name', 'legislator.id')->toArray();
                                                $particularOptions = $allocations->pluck('particular.name', 'particular.id')->toArray();
                                                $scholarshipProgramOptions = $allocations->pluck('scholarship_program.name', 'scholarship_program.id')->toArray();
                                                $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();

                                                if (count($AttributorParticularOptions) === 1) {
                                                    $set('attribution_sender_particular', key($AttributorParticularOptions));
                                                } else {
                                                    $set('attribution_sender_particular', null);
                                                }

                                                if (count($scholarshipProgramOptions) === 1) {
                                                    $set('attribution_scholarship_program', key($scholarshipProgramOptions));
                                                } else {
                                                    $set('attribution_scholarship_program', null);
                                                }

                                                if (count($legislatorOptions) === 1) {
                                                    $set('attribution_receiver', key($legislatorOptions));
                                                } else {
                                                    $set('attribution_receiver', null);
                                                }

                                                if (count($particularOptions) === 1) {
                                                    $set('attribution_receiver_particular', key($particularOptions));
                                                } else {
                                                    $set('attribution_receiver_particular', null);
                                                }

                                                if (count($appropriationYearOptions) === 1) {
                                                    $set('allocation_year', key($appropriationYearOptions));
                                                    $appropriationType = self::getAppropriationTypeOptions(key($appropriationYearOptions));

                                                    if (count($appropriationType) === 1) {
                                                        $set('attribution_appropriation_type', key($appropriationType));
                                                    }
                                                } else {
                                                    $set('allocation_year', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live()
                                            ->validationAttribute('legislator'),

                                        Select::make('attribution_sender_particular')
                                            ->label('Particular')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislatorId = $get('attribution_sender');

                                                if ($legislatorId) {
                                                    $allocation = Allocation::whereHas('particular')
                                                        ->where('attributor_id', $legislatorId)
                                                        ->with('attributorParticular.subParticular')
                                                        ->get();

                                                    return $allocation->mapWithKeys(function ($allocation) {
                                                        $particular = $allocation->attributorParticular;
                                                        $subParticular = $particular->subParticular->name ?? '';
                                                        $formattedName = '';

                                                        if ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
                                                            $regionName = $particular->district->province->region->name ?? '';
                                                            $formattedName = "{$subParticular} - {$regionName}";
                                                        } else {
                                                            $formattedName = $subParticular;
                                                        }

                                                        return [$particular->id => $formattedName];
                                                    })->toArray() ?: ['no_particular' => 'No particulars available'];
                                                }

                                                return ['no_particular' => 'No particulars available. Select an attributor first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_particular')
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if (!$state) {
                                                    $set('attribution_scholarship_program', null);
                                                    $set('attribution_receiver', null);
                                                    $set('attribution_receiver_particular', null);
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

                                                    return;
                                                }

                                                $attributorId = $get('attribution_sender');

                                                $allocations = Allocation::where('attributor_id', $attributorId)
                                                    ->where('attributor_particular_id', $state)
                                                    ->with('particular', 'scholarship_program')
                                                    ->get();

                                                $legislatorOptions = $allocations->pluck('legislator.name', 'legislator.id')->toArray();
                                                $particularOptions = $allocations->pluck('particular.name', 'particular.id')->toArray();
                                                $scholarshipProgramOptions = $allocations->pluck('scholarship_program.name', 'scholarship_program.id')->toArray();
                                                $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();
                                                $appropriationType = self::getAppropriationTypeOptions($state);

                                                if (count($appropriationType) === 1) {
                                                    $set('attribution_appropriation_type', key($appropriationType));
                                                }

                                                if (count($scholarshipProgramOptions) === 1) {
                                                    $set('attribution_scholarship_program', key($scholarshipProgramOptions));
                                                } else {
                                                    $set('attribution_scholarship_program', null);
                                                }

                                                if (count($legislatorOptions) === 1) {
                                                    $set('attribution_receiver', key($legislatorOptions));
                                                } else {
                                                    $set('attribution_receiver', null);
                                                }

                                                if (count($particularOptions) === 1) {
                                                    $set('attribution_receiver_particular', key($particularOptions));
                                                } else {
                                                    $set('attribution_receiver_particular', null);
                                                }

                                                if (count($appropriationYearOptions) === 1) {
                                                    $set('allocation_year', key($appropriationYearOptions));
                                                    $appropriationType = self::getAppropriationTypeOptions(key($appropriationYearOptions));

                                                    if (count($appropriationType) === 1) {
                                                        $set('attribution_appropriation_type', key($appropriationType));
                                                    }
                                                } else {
                                                    $set('allocation_year', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live()
                                            ->validationAttribute('particular'),

                                        Select::make('attribution_scholarship_program')
                                            ->label('Scholarship Program')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislatorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');

                                                if ($legislatorId) {
                                                    return ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
                                                        $query->where('attributor_id', $legislatorId)
                                                            ->where('attributor_particular_id', $particularId);
                                                    })
                                                        ->pluck('name', 'id')
                                                        ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                                                }

                                                return ['no_scholarship_program' => 'No scholarship programs available. Select a particular first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if (!$state) {
                                                    $set('attribution_receiver', null);
                                                    $set('attribution_receiver_particular', null);
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

                                                    return;
                                                }

                                                $attributorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');

                                                $allocations = Allocation::where('attributor_id', $attributorId)
                                                    ->where('attributor_particular_id', $particularId)
                                                    ->where('scholarship_program_id', $state)
                                                    ->with('particular', 'scholarship_program')
                                                    ->get();

                                                $legislatorOptions = $allocations->pluck('legislator.name', 'legislator.id')->toArray();
                                                $particularOptions = $allocations->pluck('particular.name', 'particular.id')->toArray();
                                                $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();
                                                $appropriationType = self::getAppropriationTypeOptions($state);

                                                if (count($appropriationType) === 1) {
                                                    $set('attribution_appropriation_type', key($appropriationType));
                                                }

                                                if (count($legislatorOptions) === 1) {
                                                    $set('attribution_receiver', key($legislatorOptions));
                                                } else {
                                                    $set('attribution_receiver', null);
                                                }

                                                if (count($particularOptions) === 1) {
                                                    $set('attribution_receiver_particular', key($particularOptions));
                                                } else {
                                                    $set('attribution_receiver_particular', null);
                                                }

                                                if (count($appropriationYearOptions) === 1) {
                                                    $set('allocation_year', key($appropriationYearOptions));
                                                    $appropriationType = self::getAppropriationTypeOptions(key($appropriationYearOptions));

                                                    if (count($appropriationType) === 1) {
                                                        $set('attribution_appropriation_type', key($appropriationType));
                                                    }
                                                } else {
                                                    $set('allocation_year', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live()
                                            ->validationAttribute('scholarship program'),
                                    ])
                                    ->columns(3),

                                Fieldset::make('Receiver')
                                    ->schema([
                                        Select::make('attribution_receiver')
                                            ->label('Legislator')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislator = $get('attribution_sender');

                                                if ($legislator) {
                                                    $legislatorId = $get('attribution_sender');
                                                    $particularId = $get('attribution_sender_particular');
                                                    $scholarshipProgramId = $get('attribution_scholarship_program');

                                                    $allocations = Allocation::where('attributor_id', $legislatorId)
                                                        ->where('attributor_particular_id', $particularId)
                                                        ->where('scholarship_program_id', $scholarshipProgramId)
                                                        ->with('legislator')
                                                        ->get()
                                                        ->sortBy('name')
                                                        ->pluck('legislator.name', 'legislator.id')
                                                        ->toArray();

                                                    return $allocations ?? ['no_legislator' => 'No legislators available'];
                                                }

                                                return ['no_legislator' => 'No legislators available. Complete sender fields first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_legislator')
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                if (!$state) {
                                                    $set('attribution_receiver_particular', null);
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

                                                    return;
                                                }

                                                $attributorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');
                                                $scholarshipProgramId = $get('attribution_scholarship_program');

                                                $allocations = Allocation::where('attributor_id', $attributorId)
                                                    ->where('attributor_particular_id', $particularId)
                                                    ->where('scholarship_program_id', $scholarshipProgramId)
                                                    ->with('particular', 'scholarship_program')
                                                    ->get();

                                                $particularOptions = $allocations->pluck('particular.name', 'particular.id')->toArray();
                                                $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();
                                                $appropriationType = self::getAppropriationTypeOptions($state);

                                                if (count($appropriationType) === 1) {
                                                    $set('attribution_appropriation_type', key($appropriationType));
                                                }

                                                if (count($particularOptions) === 1) {
                                                    $set('attribution_receiver_particular', key($particularOptions));
                                                } else {
                                                    $set('attribution_receiver_particular', null);
                                                }

                                                if (count($appropriationYearOptions) === 1) {
                                                    $set('allocation_year', key($appropriationYearOptions));
                                                    $appropriationType = self::getAppropriationTypeOptions(key($appropriationYearOptions));

                                                    if (count($appropriationType) === 1) {
                                                        $set('attribution_appropriation_type', key($appropriationType));
                                                    }
                                                } else {
                                                    $set('allocation_year', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live()
                                            ->validationAttribute('legislator'),

                                        Select::make('attribution_receiver_particular')
                                            ->label('Particular')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
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
                                                                $name = $particular->subParticular->name . '-' . $particular->partylist->name;
                                                            } elseif ($particular->subParticular->name === 'District') {
                                                                if ($particular->district->underMunicipality) {
                                                                    $name = $particular->subParticular->name . ' - ' . $particular->district->name . ', ' . $particular->district->underMunicipality->name . ', ' . $particular->district->province->name;
                                                                } else {
                                                                    $name = $particular->subParticular->name . ' - ' . $particular->district->name . ', ' . $particular->district->province->name;
                                                                }
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

                                                    return $particularOptions ?: ['no_particular' => 'No particulars available'];
                                                }

                                                return ['no_particular' => 'No particulars available. Select a legislator first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_particular')
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                if (!$state) {
                                                    $set('allocation_year', null);
                                                    $set('attribution_appropriation_type', null);

                                                    return;
                                                }

                                                $attributorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');
                                                $scholarshipProgramId = $get('attribution_scholarship_program');
                                                $legislatorId = $get('attribution_receiver');

                                                $allocations = Allocation::where('attributor_id', $attributorId)
                                                    ->where('attributor_particular_id', $particularId)
                                                    ->where('scholarship_program_id', $scholarshipProgramId)
                                                    ->where('legislator_id', $legislatorId)
                                                    ->with('particular', 'scholarship_program')
                                                    ->get();

                                                $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();
                                                $appropriationType = self::getAppropriationTypeOptions($state);

                                                if (count($appropriationType) === 1) {
                                                    $set('attribution_appropriation_type', key($appropriationType));
                                                }

                                                if (count($appropriationYearOptions) === 1) {
                                                    $set('allocation_year', key($appropriationYearOptions));
                                                    $appropriationType = self::getAppropriationTypeOptions(key($appropriationYearOptions));

                                                    if (count($appropriationType) === 1) {
                                                        $set('attribution_appropriation_type', key($appropriationType));
                                                    }
                                                } else {
                                                    $set('allocation_year', null);
                                                }
                                            })
                                            ->reactive()
                                            ->live()
                                            ->validationAttribute('particular'),

                                        Select::make('allocation_year')
                                            ->label('Appropriation Year')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $attributorId = $get('attribution_sender');
                                                $legislatorId = $get('attribution_receiver');
                                                $attributorParticularId = $get('attribution_sender_particular');
                                                $particularId = $get('attribution_receiver_particular');
                                                $scholarshipProgramId = $get('attribution_scholarship_program');

                                                return $legislatorId
                                                    ? self::getAllocationYear($attributorId, $legislatorId, $attributorParticularId, $particularId, $scholarshipProgramId)
                                                    : ['no_allocation' => 'No appropriation year available. Select a scholarship program first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_allocation')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('attribution_appropriation_type', null);
                                                    return;
                                                }

                                                $appropriationType = self::getAppropriationTypeOptions($state);

                                                if (count($appropriationType) === 1) {
                                                    $set('attribution_appropriation_type', key($appropriationType));
                                                }
                                            })
                                            ->reactive()
                                            ->live()
                                            ->validationAttribute('appropriation year'),

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
                                            ->live()
                                            ->validationAttribute('appropriation year'),

                                        Select::make('tvi_id')
                                            ->label('Institution')
                                            ->relationship('tvi', 'name')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function () {
                                                return TVI::whereNot('name', 'Not Applicable')
                                                    ->has('trainingPrograms')
                                                    ->orderBy('name')
                                                    ->get()
                                                    ->mapWithKeys(function ($tvi) {
                                                        $schoolId = $tvi->school_id;
                                                        $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;

                                                        return [$tvi->id => $formattedName];
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
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $scholarshipProgramId = $get('attribution_scholarship_program');
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
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function () {
                                                return Abdd::whereNull('deleted_at')
                                                    ->whereNot('name', 'Not Applicable')
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_abdd' => 'No ABDD sectors available'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_abdd')
                                            ->validationAttribute('ABDD sector'),

                                        Select::make('delivery_mode_id')
                                            ->label('Delivery Mode')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function () {
                                                return DeliveryMode::whereNull('deleted_at')
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_delivery_mode' => 'No delivery modes available'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode')
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if (!$state) {
                                                    $set('learning_mode_id', null);
                                                }

                                                $set('learning_mode_id', null);
                                            })
                                            ->reactive()
                                            ->live()
                                            ->validationAttribute('delivery mode'),

                                        Select::make('learning_mode_id')
                                            ->label('Learning Mode')
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $deliveryModeId = $get('delivery_mode_id');

                                                if ($deliveryModeId) {
                                                    return DeliveryMode::find($deliveryModeId)?->learningMode
                                                        ->sortBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray() ?: ['no_learning_modes' => 'No learning modes available'];
                                                }

                                                return ['no_learning_modes' => 'No learning modes available. Select a delivery mode first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_learning_modes')
                                            ->reactive()
                                            ->live()
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
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No attribution targets available')
            ->columns([
                TextColumn::make('allocation.attributorParticular.subParticular.fundSource.name')
                    ->label('Fund Source')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $attributor = $record->allocation->attributor;
                        $particular = $attributor ? $record->allocation->attributorParticular : $record->allocation->particular;
                        $fundSource = $particular->subParticular ? $particular->subParticular->fundSource->name : '-';

                        return $fundSource;
                    }),

                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Source of Fund')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.attributor.name')
                    ->label('Attributor')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->allocation->attributor ? $record->allocation->attributor->name : '-';
                    }),

                TextColumn::make('allocation.attributorParticular.subParticular.name')
                    ->label('Attributor Particular')
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('allocation.attributorParticular.subParticular', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        })
                            ->orWhereHas('allocation.attributorParticular.district.province.region', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            });
                    })
                    ->toggleable()
                    ->getStateUsing(function ($record) {

                        $particularType = $record->allocation->attributorParticular->subParticular;
                        $particular = $record->allocation->attributorParticular;
                        $district = $particular->district ?? null;
                        $municipalityName = $district->underMunicipality->name ?? null;

                        if ($district->name === 'Not Applicable') {
                            if ($particularType->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else if ($particularType?->name === 'RO Regular' || $particularType?->name === 'CO Regular') {
                                return "{$particularType->name} - {$particular->district->province->region->name}";
                            } else {
                                return $particular->subParticular->name ?? '';
                            }
                        }
                         else {
                            if ($municipalityName === null) {
                                return "{$particular->subParticular->name} - {$district->name}, {$district->province->name}";
                            } else {
                                return "{$particular->subParticular->name} - {$district->name}, {$municipalityName}, {$district->province->name}";
                            }
                         }
                    }),

                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.particular.subParticular.name')
                    ->label('Particular')
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('allocation.particular.subParticular', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        })
                            ->orWhereHas('allocation.particular.district', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('allocation.particular.district.province', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('allocation.particular.district.underMunicipality', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('allocation.particular.partylist', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            });
                    })
                    ->toggleable()
                    ->getStateUsing(function ($record) {

                        $particularType = $record->allocation->attributorParticular->subParticular;
                        $particular = $record->allocation->attributorParticular;
                        $district = $particular->district ?? null;
                        $municipalityName = $district->underMunicipality->name ?? null;

                        if ($district->name === 'Not Applicable') {
                            if ($particularType->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else if ($particularType?->name === 'RO Regular' || $particularType?->name === 'CO Regular') {
                                return "{$particularType->name} - {$particular->district->province->region->name}";
                            } else {
                                return $particular->subParticular->name ?? '';
                            }
                        }
                         else {
                            if ($municipalityName === null) {
                                return "{$particular->subParticular->name} - {$district->name}, {$district->province->name}";
                            } else {
                                return "{$particular->subParticular->name} - {$district->name}, {$municipalityName}, {$district->province->name}";
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
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('tvi', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('school_id', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $schoolId = $record->tvi->school_id ?? '';
                        $institutionName = $record->tvi->name ?? '';

                        if ($schoolId) {
                            return "{$schoolId} - {$institutionName}";
                        }

                        return $institutionName;
                    }),

                TextColumn::make('tvi.tviClass.name')
                    ->label('Institution Class')
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('tvi', function ($q) use ($search) {
                            $q->whereHas('tviType', function ($q2) use ($search) {
                                $q2->where('name', 'like', "%{$search}%");
                            })->orWhereHas('tviClass', function ($q3) use ($search) {
                                $q3->where('name', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $institutionType = $record->tvi->tviType->name ?? '';
                        $institutionClass = $record->tvi->tviClass->name ?? '';

                        return "{$institutionType} - {$institutionClass}";
                    }),

                TextColumn::make('district.province.name')
                    ->label('Location')
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('tvi.district', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhereHas('province', function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                        ->orWhereHas('region', function ($q) use ($search) {
                                            $q->where('name', 'like', "%{$search}%");
                                        });
                                })
                                ->orWhereHas('underMunicipality', function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->toggleable()
                    ->getStateUsing(fn($record) => self::getLocationNames($record)),

                TextColumn::make('qualification_title_soc_code')
                    ->label('SOC Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title_name')
                    ->label('Qualification Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.scholarshipProgram.name')
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
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make('targetStatus.desc')
                    ->label('Status')
                    ->toggleable(),
            ])
            ->recordClasses(fn($record) => $record->is_new && !$record->hasBeenSeenByUser(Auth::id())
                ? 'bg-gray-200 dark:bg-gray-800 font-bold'
                : '')
            ->recordUrl(
                fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id]),
            )
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter attribution target')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed())
                        ->visible(fn() => !Auth::user()->hasRole(['SMD Focal', 'RO'])),

                    Action::make('viewHistory')
                        ->label('View History')
                        ->icon('heroicon-o-clock')
                        ->url(fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id])),

                    Action::make('viewComment')
                        ->label('View Comments')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->badge(fn($record) => $record->comments()->whereDoesntHave('readByUsers', function ($query) {
                            $query->where('user_id', auth()->id());
                        })->count() > 0 ? $record->comments()->whereDoesntHave('readByUsers', function ($query) {
                            $query->where('user_id', auth()->id());
                        })->count() : null)
                        ->color(fn($record) => $record->comments()
                            ->whereDoesntHave('readByUsers', function ($query) {
                                $query->where('user_id', auth()->id());
                            })
                            ->exists() ? 'primary' : 'gray')
                        ->modalHeading('Comments')
                        ->modalSubmitActionLabel('Comment')
                        ->modalWidth('3xl')
                        ->modalContent(function (Target $record): HtmlString {
                            $userId = auth()->id();

                            $record->comments()->each(function ($comment) use ($userId) {
                                if ($comment->readByUsers()->where('user_id', $userId)->doesntExist()) {
                                    $comment->readByUsers()->create(['user_id' => $userId]);
                                }
                            });

                            $comments = $record->comments()->latest()->get();

                            $commentsHtml = collect($comments)->map(function ($comment) {
                                $username = e($comment->user->name);
                                $content = e($comment->content);
                                $timeAgo = $comment->created_at->diffForHumans();
                                $createdAt = $comment->created_at->format('j M, g:i A');
                                $createdAtTooltip = $comment->created_at->format('M j y, g:i A');

                                return "
                                    <div class='p-2'>
                                        <div class='bg-gray-100 dark:bg-gray-800 p-4 rounded-lg text-gray-900 dark:text-gray-100'>
                                            <div class='flex justify-between items-center text-gray-900 dark:text-gray-100 mb-2'>
                                                <span class='font-bold' style='margin-right: 10px;'>{$username}</span>
                                                <small class='text-gray-500 dark:text-gray-400' title='{$createdAtTooltip}'>
                                                    {$createdAt}
                                                </small>
                                            </div>
                                            <div class='text-gray-800 dark:text-gray-200'>{$content}</div>
                                        </div>
                                    </div>
                                ";
                            })->implode('');

                            return new HtmlString("
                                <style>
                                    .custom-scrollbar::-webkit-scrollbar {
                                        width: 8px;
                                    }

                                    .custom-scrollbar::-webkit-scrollbar-thumb {
                                        background: #777;
                                        border-radius: 4px;
                                    }
                                </style>

                                <div class='max-h-96 overflow-y-auto pb-2 custom-scrollbar flex flex-col-reverse'>
                                    " . ($commentsHtml ?: "<p class='text-gray-500 dark:text-gray-400 text-center p-4 mt-4'>No comments yet.</p>") . "
                                </div>
                            ");
                        })
                        ->form([
                            Textarea::make('content')
                                ->label('')
                                ->placeholder('Write your comment here')
                                ->required()
                                ->markAsRequired(false),
                        ])
                        ->action(function (array $data, $record): void {
                            $comment = TargetComment::create([
                                'target_id' => $record->id,
                                'user_id' => auth()->id(),
                                'content' => $data['content'],
                            ]);

                            $comment->readByUsers()->create(['user_id' => auth()->id()]);
                        }),

                    Action::make('setAsCompliant')
                        ->label('Set as Compliant')
                        ->icon('heroicon-o-check-circle')
                        ->url(fn($record) => route('filament.admin.resources.compliant-targets.create', ['record' => $record->id]))
                        ->visible(fn() => !Auth::user()->hasRole('TESDO'))
                        ->hidden(fn($record) => $record->trashed()),

                    Action::make('setAsNonCompliant')
                        ->label('Set as Non-compliant')
                        ->icon('heroicon-o-x-circle')
                        ->url(fn($record) => route('filament.admin.resources.non-compliant-targets.create', ['record' => $record->id]))
                        ->visible(fn() => !Auth::user()->hasRole('TESDO'))
                        ->hidden(fn($record) => $record->trashed()),

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
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete attribution target')),

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
                ]),
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
                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected targets have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete attribution target ')),

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

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected targets have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore attribution target ')),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected targets have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete attribution target ')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomAttributionTargetExport::make()
                                ->withColumns([
                                    // Column::make('abscap_id')
                                    //     ->heading('Absorptive Capacity'),

                                    Column::make('allocation.attributorParticular.subParticular.fundSource.name')
                                        ->heading('Fund Source')
                                        ->getStateUsing(function ($record) {
                                            $attributor = $record->allocation->attributor;
                                            $particular = $attributor ? $record->allocation->attributorParticular : $record->allocation->particular;
                                            $fundSource = $particular->subParticular ? $particular->subParticular->fundSource->name : '-';

                                            return $fundSource;
                                        }),

                                    Column::make('allocation.soft_or_commitment')
                                        ->heading('Source of Fund'),

                                    Column::make('allocation.attributor.name')
                                        ->heading('Attributor')
                                        ->getStateUsing(function ($record) {
                                            return $record->allocation->attributor ? $record->allocation->attributor->name : '-';
                                        }),

                                    Column::make('allocation.attributorParticular.subParticular.name')
                                        ->heading('Attributor Particular')
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
                                                } else {
                                                    return "{$particular->subParticular->name} - {$districtName}, {$district->province->name}";
                                                }
                                            }
                                        }),

                                    Column::make('allocation.legislator.name')
                                        ->heading('Legislator'),

                                    Column::make('allocation.particular.subParticular.name')
                                        ->heading('Particular')
                                        ->getStateUsing(function ($record) {
                                            $particular = $record->allocation->particular;
                                            $district = $particular->district;
                                            $municipality = $district ? $district->underMunicipality : '';

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
                                                if ($municipalityName === '') {
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

                                    Column::make('tvi.school_id')
                                        ->heading('School ID')
                                        ->getStateUsing(function ($record) {
                                            return $record->tvi->school_id ? $record->tvi->school_id : '-';
                                        }),

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

                                    Column::make('qualification_title_soc_code')
                                        ->heading('SOC Code'),

                                    Column::make('qualification_title_name')
                                        ->heading('Qualification Title'),

                                    Column::make('qualification_title.scholarshipProgram.name')
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
                                        ->heading('Learning Mode')
                                        ->getStateUsing(fn($record) => $record->learningMode->name ?? '-'),

                                    Column::make('number_of_slots')
                                        ->heading('Slots'),

                                    Column::make('training_cost_per_slot')
                                        ->heading('Training Cost')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_training_cost_pcc'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('cost_of_toolkit_per_slot')
                                        ->heading('Cost of Toolkit')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_cost_of_toolkit_pcc'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('training_support_fund_per_slot')
                                        ->heading('Training Support Fund')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_training_support_fund'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('assessment_fee_per_slot')
                                        ->heading('Assessment Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_assessment_fee'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('entrepreneurship_fee_per_slot')
                                        ->heading('Entrepreneurship Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_entrepreneurship_fee'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('new_normal_assistance_per_slot')
                                        ->heading('New Normal Assistance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_new_normal_assistance'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('accident_insurance_per_slot')
                                        ->heading('Accident Insurance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_accident_insurance'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('book_allowance_per_slot')
                                        ->heading('Book Allowance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_book_allowance'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('uniform_allowance_per_slot')
                                        ->heading('Uniform Allowance')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_uniform_allowance'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('misc_fee_per_slot')
                                        ->heading('Miscellaneous Fee')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_misc_fee'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_amount_per_slot')
                                        ->heading('PCC')
                                        ->getStateUsing(fn($record) => self::calculateCostPerSlot($record, 'total_amount'))
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_training_cost_pcc')
                                        ->heading('Total Training Cost')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_cost_of_toolkit_pcc')
                                        ->heading('Total Cost of Toolkit')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_training_support_fund')
                                        ->heading('Total Training Support Fund')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_assessment_fee')
                                        ->heading('Total Assessment Fee')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_entrepreneurship_fee')
                                        ->heading('Total Entrepreneurship Fee')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_new_normal_assisstance')
                                        ->heading('Total New Normal Assistance')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_accident_insurance')
                                        ->heading('Total Accident Insurance')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_book_allowance')
                                        ->heading('Total Book Allowance')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_uniform_allowance')
                                        ->heading('Total Uniform Allowance')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_misc_fee')
                                        ->heading('Total Miscellaneous Fee')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('total_amount')
                                        ->heading('Total PCC')
                                        ->format('"₱ "#,##0.00'),
                                    Column::make('targetStatus.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Pending Attribution Targets')
                        ]),
                ])
                    ->label('Select Action'),
            ]);
    }

    private static function getParticularOptions($legislatorId)
    {
        $legislator = Legislator::with('particular.district.municipality')->find($legislatorId);

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

    protected static function getAllocationYear($attributorId, $legislatorId, $attributorParticularId, $particularId, $scholarshipProgramId)
    {
        $yearNow = date('Y');

        return Allocation::where('attributor_id', $attributorId)
            ->where('legislator_id', $legislatorId)
            ->where('attributor_particular_id', $attributorParticularId)
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
            $municipalityName = $tvi->municipality->name ?? '';

            if ($municipalityName) {
                return "{$districtName}, {$municipalityName}, {$provinceName}";
            } else {
                return "{$districtName}, {$provinceName}";
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

        return !empty($qualificationTitles) ? $qualificationTitles : ['no_qualification_title' => 'No qualification titles available'];
    }

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId)
    {
        return ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
            $query->where('attributor_particular_id', $legislatorId)
                ->where('scholarship_program_id', $particularId);
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
        $user = auth()->user();
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
                    $subQuery->whereNotNull('attributor_id')
                        ->where('soft_or_commitment', 'Commitment');
                });

            if (!request()->is('*/edit') && $routeParameter && filter_var($routeParameter, FILTER_VALIDATE_INT)) {
                $query->where('region_id', (int) $routeParameter);
            }

            if ($user) {
                $userRegionIds = $user->region()->pluck('regions.id')->toArray();
                $userProvinceIds = $user->province()->pluck('provinces.id')->toArray();
                $userDistrictIds = $user->district()->pluck('districts.id')->toArray();
                $userMunicipalityIds = $user->municipality()->pluck('municipalities.id')->toArray();

                $isPO_DO = !empty($userProvinceIds) || !empty($userMunicipalityIds) || !empty($userDistrictIds);
                $isRO = !empty($userRegionIds);

                if ($isPO_DO) {
                    $query->where(function ($q) use ($userProvinceIds, $userDistrictIds, $userMunicipalityIds) {
                        if (!empty($userDistrictIds) && !empty($userMunicipalityIds)) {
                            $q->whereHas('district', function ($districtQuery) use ($userDistrictIds) {
                                $districtQuery->whereIn('districts.id', $userDistrictIds);
                            })->whereHas('municipality', function ($municipalityQuery) use ($userMunicipalityIds) {
                                $municipalityQuery->whereIn('municipalities.id', $userMunicipalityIds);
                            });
                        } elseif (!empty($userMunicipalityIds)) {
                            $q->whereHas('municipality', function ($municipalityQuery) use ($userMunicipalityIds) {
                                $municipalityQuery->whereIn('municipalities.id', $userMunicipalityIds);
                            });
                        } elseif (!empty($userDistrictIds)) {
                            $q->whereHas('district', function ($districtQuery) use ($userDistrictIds) {
                                $districtQuery->whereIn('districts.id', $userDistrictIds);
                            });
                        } elseif (!empty($userProvinceIds)) {
                            $q->whereHas('district.province', function ($districtQuery) use ($userProvinceIds) {
                                $districtQuery->whereIn('province_id', $userProvinceIds);
                            });

                            $q->orWhereHas('municipality.province', function ($municipalityQuery) use ($userProvinceIds) {
                                $municipalityQuery->whereIn('province_id', $userProvinceIds);
                            });
                        }
                    });
                }

                if ($isRO) {
                    $query->where(function ($q) use ($userRegionIds) {
                        $q->orWhereHas('district.province', function ($provinceQuery) use ($userRegionIds) {
                            $provinceQuery->whereIn('region_id', $userRegionIds);
                        });

                        $q->orWhereHas('municipality.province', function ($provinceQuery) use ($userRegionIds) {
                            $provinceQuery->whereIn('region_id', $userRegionIds);
                        });
                    });
                }
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
            'index' => Pages\ListAttributionTargets::route('/'),
            'create' => Pages\CreateAttributionTarget::route('/create'),
            'edit' => Pages\EditAttributionTarget::route('/{record}/edit'),
        ];
    }
}
