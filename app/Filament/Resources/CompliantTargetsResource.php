<?php

namespace App\Filament\Resources;

use App\Exports\CompliantTargetExport;
use App\Exports\CustomExport\CustomCompliantTarget;
use App\Filament\Resources\CompliantTargetsResource\Pages;
use App\Models\Abdd;
use App\Models\Allocation;
use App\Models\DeliveryMode;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\SubParticular;
use App\Models\Target;
use App\Models\TargetRemark;
use App\Models\TargetStatus;
use App\Models\Tvi;
use App\Models\User;
use App\Policies\TargetPolicy;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CompliantTargetsResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Compliant Targets";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        $urlParams = request()->get('record');
        $record = Target::find($urlParams);

        return $form->schema(function ($record) {
            $createCommonFields = function ($record, $isDisabled = true) {
                return [
                    Fieldset::make('Sender')
                        ->schema([
                            Select::make('attribution_sender')
                                ->label('Attributor')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->default($record->allocation->attributor_id ?? null)
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
                                ->afterStateUpdated(function ($state, $record, callable $set) {
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

                                    // $set('attribution_appropriation_type', $appropriationType);
                
                                    // if (count($appropriationYearOptions) === 1) {
                                    //     $set('attribution_appropriation_type', key($appropriationYearOptions));
                                    // }
                
                                    // $currentYear = now()->year;
                
                                    if (count($AttributorParticularOptions) === 1) {
                                        $set('attribution_sender_particular', key($AttributorParticularOptions));
                                    } else {
                                        $set('attribution_sender_particular', null);
                                    }

                                    if (!$record) {
                                        if (count($scholarshipProgramOptions) === 1) {
                                            $set('attribution_scholarship_program', key($scholarshipProgramOptions));
                                        } else {
                                            $set('attribution_scholarship_program', null);
                                        }
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
                                ->disabled()
                                ->dehydrated(),

                            Select::make('attribution_sender_particular')
                                ->label('Particular')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->default($record->allocation->attributor_particular_id ?? null)
                                ->options(function ($get) {
                                    $legislatorId = $get('attribution_sender');

                                    if ($legislatorId) {
                                        $allocation = Allocation::whereHas('particular')
                                            ->where('attributor_id', $legislatorId)
                                            ->get();

                                        return $allocation->pluck('attributorParticular.subParticular.name', 'attributorParticular.id')
                                            ->toArray() ?: ['no_particular' => 'No particulars available'];
                                    }
                                    return ['no_particular' => 'No particulars available. Select an attributor first.'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_particular')
                                ->afterStateUpdated(function ($state, $record, callable $set, callable $get) {
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

                                    // $set('attribution_appropriation_type', $appropriationType);
                
                                    if (count($appropriationType) === 1) {
                                        $set('attribution_appropriation_type', key($appropriationType));
                                    }

                                    if (!$record) {
                                        if (count($scholarshipProgramOptions) === 1) {
                                            $set('attribution_scholarship_program', key($scholarshipProgramOptions));
                                        } else {
                                            $set('attribution_scholarship_program', null);
                                        }
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
                                ->disabled()
                                ->dehydrated(),

                            Select::make('attribution_scholarship_program')
                                ->label('Scholarship Program')
                                ->required()
                                ->markAsRequired(false)
                                ->default($record ? $record->allocation->scholarship_program_id : null)
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->options(function ($get) {
                                    $legislatorId = $get('attribution_sender');
                                    $particularId = $get('attribution_sender_particular');

                                    if ($legislatorId) {
                                        $programs = ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
                                            $query->where('attributor_id', $legislatorId)
                                                ->when($particularId, fn($q) => $q->where('attributor_particular_id', $particularId));
                                        })->pluck('name', 'id')->toArray();

                                        return !empty($programs) ? $programs : ['no_scholarship_program' => 'No scholarship program available'];
                                    }

                                    // If no attributor is selected, show all scholarship programs
                                    return ScholarshipProgram::pluck('name', 'id')->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
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
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(3),

                    Fieldset::make('Receiver')
                        ->schema([
                            Select::make('attribution_receiver')
                                ->label('Legislator')
                                ->required()
                                ->markAsRequired(false)
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->default($record ? $record->allocation->legislator_id : null)
                                ->options(function ($get) {
                                    // Get input values
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
                                            ->pluck('legislator.name', 'legislator.id')
                                            ->toArray();

                                        return $allocations ?? ['no_legislator' => 'No Legislator Available.'];
                                    } else {
                                        $scholarshipProgramId = $get('attribution_scholarship_program');

                                        $allocations = Allocation::where('scholarship_program_id', $scholarshipProgramId)
                                            ->with('legislator')
                                            ->get()
                                            ->pluck('legislator.name', 'legislator.id')
                                            ->toArray();

                                        return $allocations ?? ['no_legislator' => 'No Legislator Available.'];
                                    }

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

                                    // $set('attribution_appropriation_type', $appropriationType);
                
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
                                ->disabled()
                                ->dehydrated(),

                            Select::make('attribution_receiver_particular')
                                ->label('Particular')
                                ->required()
                                ->markAsRequired(false)
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->default($record ? $record->allocation->particular_id : null)
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

                                    // $set('attribution_appropriation_type', $appropriationType);
                
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
                                ->disabled()
                                ->dehydrated(),

                            Select::make('allocation_year')
                                ->label('Appropriation Year')
                                ->required()
                                ->markAsRequired(false)
                                ->native(false)
                                ->default($record ? $record->allocation->year : null)
                                ->options(function ($get) {
                                    $attributorId = $get('attribution_sender');
                                    $legislatorId = $get('attribution_receiver');
                                    $attributorParticularId = $get('attribution_sender_particular');
                                    $particularId = $get('attribution_receiver_particular');
                                    $scholarshipProgramId = $get('attribution_scholarship_program');

                                    return $legislatorId
                                        ? self::getAllocationYear($attributorId, $legislatorId, $attributorParticularId, $particularId, $scholarshipProgramId)
                                        : ['no_allocation' => 'No appropriation years available. Select a scholarship program first.'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_allocation')
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (!$state) {
                                        $set('attribution_appropriation_type', null);
                                        return;
                                    }

                                    $appropriationType = self::getAppropriationTypeOptions($state);

                                    // $set('attribution_appropriation_type', $appropriationType);
                
                                    if (count($appropriationType) === 1) {
                                        $set('attribution_appropriation_type', key($appropriationType));
                                    }
                                })
                                ->reactive()
                                ->live()
                                ->disabled()
                                ->dehydrated(),

                            Select::make('attribution_appropriation_type')
                                ->label('Appropriation Type')
                                ->required()
                                ->markAsRequired(false)
                                ->native(false)
                                ->default($record ? $record->appropriation_type : null)
                                ->options(function ($get) {
                                    $year = $get('allocation_year');

                                    return $year
                                        ? self::getAppropriationTypeOptions($year)
                                        : ['no_allocation' => 'No appropriation types available. Select an appropriation year first.'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_allocation')
                                ->reactive()
                                ->live()
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
                                ->default($record ? $record->tvi_id : null)
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
                                ->disableOptionWhen(fn($value) => $value === 'no_tvi')
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if (!$state) {
                                        $set('qualification_title_id', null);
                                    }

                                    $set('qualification_title_id', null);

                                })
                                ->reactive()
                                ->live()
                                ->disabled()
                                ->dehydrated(),

                            Select::make('qualification_title_id')
                                ->label('Qualification Title')
                                ->required()
                                ->markAsRequired(false)
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->default($record ? $record->qualification_title_id : null)
                                ->options(function ($get) {
                                    $scholarshipProgramId = $get('attribution_scholarship_program');
                                    $tviId = $get('tvi_id');
                                    $year = $get('allocation_year');

                                    return $scholarshipProgramId
                                        ? self::getQualificationTitles($scholarshipProgramId, $tviId, $year)
                                        : ['no_qualification_title' => 'No qualification titles available. Select a scholarship program first.'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_qualification_title')
                                ->disabled()
                                ->dehydrated(),

                            Select::make('delivery_mode_id')
                                ->label('Delivery Mode')
                                ->required()
                                ->markAsRequired(false)
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->default($record ? $record->delivery_mode_id : null)
                                ->options(function () {
                                    $deliveryModes = DeliveryMode::all();

                                    return $deliveryModes->isNotEmpty()
                                        ? $deliveryModes->pluck('name', 'id')->toArray()
                                        : ['no_delivery_mode' => 'No delivery modes available'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode')
                                ->disabled()
                                ->dehydrated(),

                            Select::make('learning_mode_id')
                                ->label('Learning Mode')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->default($record ? $record->learning_mode_id : null)
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
                                ->default($record ? $record->abdd_id : null)
                                ->options(function () {
                                    return Abdd::whereNull('deleted_at')
                                        ->pluck('name', 'id')
                                        ->toArray() ?: ['no_abdd' => 'No ABDD sectors available'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_abdd')
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('number_of_slots')
                                ->label('Slots')
                                ->placeholder('Enter number of slots')
                                ->required()
                                ->default($record ? $record->number_of_slots : null)
                                ->markAsRequired(false)
                                ->autocomplete(false)
                                ->integer()
                                ->rules(['min: 10', 'max: 25'])
                                ->validationAttribute('Number of Slots')
                                ->validationMessages([
                                    'min' => 'The number of slots must be at least 10.',
                                    'max' => 'The number of slots must not exceed 25.'
                                ])
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('per_capita_cost')
                                ->label('Per Capita Cost')
                                ->placeholder('Enter per capita cost')
                                ->required()
                                ->default($record ? $record->total_amount / $record->number_of_slots : null)
                                ->markAsRequired(false)
                                ->autocomplete(false)
                                ->prefix('₱')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('target_id')
                                ->label('')
                                ->default($record ? $record->id : 'id')
                                ->extraAttributes(['class' => 'hidden'])
                                ->required()
                                ->disabled()
                                ->dehydrated()
                                ->numeric(),
                        ])
                        ->columns(3)
                ];
            };

            if ($record) {
                return [
                    Section::make('Target Details')->schema($createCommonFields($record, false))->columns(2),
                ];
            } else {
                $urlParams = request()->get('record');
                $record = Target::find($urlParams);

                return [
                    Section::make('Target Information')->schema($createCommonFields($record, true))->columns(2),
                ];
            }
        });
    }




    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

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

                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Source of Fund'),

                TextColumn::make('attributionAllocation.legislator.name')
                    ->label('Attributor')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        return $record->allocation->attributor ? $record->allocation->attributor->name : '-';
                    }),

                TextColumn::make('attributionAllocation.legislator.particular.subParticular')
                    ->label('Attribution Particular')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $particular = $record->allocation->attributorParticular;

                        if (!$particular) {
                            return '-';
                        }

                        $district = $particular->district;
                        $districtName = $district ? $district->name : '';

                        if ($districtName === 'Not Applicable') {
                            if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else {
                                return $particular->subParticular->name ?? '-';
                            }
                        } else {
                            if ($particular->district->underMunicipality) {
                                return "{$particular->subParticular->name} - {$districtName}, {$district->underMunicipality->name}, {$district->province->name}";
                            } else {
                                return "{$particular->subParticular->name} - {$districtName}, {$district->province->name}";
                            }
                        }
                    }),


                TextColumn::make('allocation.legislator.name')
                    ->label('Legislator'),

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
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.year')
                    ->label('Allocation Year')
                    ->searchable()
                    ->toggleable(),


                TextColumn::make('location')
                    ->label('Address')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => self::getLocationNames($record)),

                TextColumn::make('tvi.tviType.name')
                    ->label('Institution Class')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $institutionType = $record->tvi->tviType->name ?? '';
                        $institutionClass = $record->tvi->tviClass->name ?? '';

                        return "{$institutionType} - {$institutionClass}";
                    }),

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


                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector'),

                TextColumn::make('qualification_title.trainingProgram.tvet.name')
                    ->label('TVET Sector'),

                TextColumn::make('abdd.name')
                    ->label('ABDD Sector'),

                TextColumn::make('number_of_slots')
                    ->searchable()
                    ->toggleable()
                    ->label('No. of Slots'),

                TextColumn::make('total_amount')
                    ->searchable()
                    ->toggleable()
                    ->label('Total Amount')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make('targetStatus.desc')
                    ->label('Status'),
            ])
            ->filters([
                // Add any filters if needed
            ])
            ->actions([
                ActionGroup::make([
                    // EditAction::make(),
                    Action::make('viewHistory')
                        ->label('View History')
                        ->url(fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id]))
                        ->icon('heroicon-o-magnifying-glass'),
                    Action::make('viewComment')
                        ->label('View Comments')
                        ->url(fn($record) => route('filament.admin.resources.targets.showComments', ['record' => $record->id]))
                        ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                    DeleteAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete target ')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete compliant target ')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomCompliantTarget::make()
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

                                    Column::make('allocation.soft_or_commitment')
                                        ->heading('Soft or Commitment'),

                                    Column::make('allocation.attributor.name')
                                        ->heading('Attributor')
                                        ->getStateUsing(function ($record) {
                                            return $record->allocation->attributor ? $record->allocation->attributor->name : '-';
                                        }),

                                    Column::make('allocation.attributorParticular.subParticular')
                                        ->heading('Attributor Particular')
                                        ->getStateUsing(function ($record) {
                                            $particular = $record->allocation->attributorParticular;

                                            if (!$particular) {
                                                return '-';
                                            }

                                            $district = $particular->district;
                                            $districtName = $district ? $district->name : '';

                                            if ($districtName === 'Not Applicable') {
                                                if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                                    return "{$particular->subParticular->name} - {$particular->partylist->name}";
                                                } else {
                                                    return $particular->subParticular->name ?? '-';
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
                                        ->heading('No. of slots'),

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
                                ->withFilename(date('m-d-Y') . ' - compliant_target_export')
                        ]),
                ])
                    ->label('Select Action'),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id])
            );
    }

    public static function getRelations(): array
    {
        return [
            // Define any relations here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompliantTargets::route('/'),
            'create' => Pages\CreateCompliantTargets::route('/create'),
            // 'edit' => Pages\EditCompliantTargets::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        $routeParameter = request()->route('record');
        $compliantStatus = TargetStatus::where('desc', 'Compliant')->first();

        if ($compliantStatus) {
            $query->withoutGlobalScopes([SoftDeletingScope::class])
                ->where('target_status_id', '=', $compliantStatus->id); // Use '=' for comparison

            if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
                $query->where('region_id', (int) $routeParameter);
            }
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

        return $query;
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

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId)
    {
        $scholarshipPrograms = ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
            $query->where('legislator_id', $legislatorId)
                ->where('particular_id', $particularId);
        })->pluck('name', 'id')->toArray();

        return empty($scholarshipPrograms) ? ['' => 'No Scholarship Program Available'] : $scholarshipPrograms;
    }

    protected static function getAllocationYear($attributorId, $legislatorId, $attributorParticularId, $particularId, $scholarshipProgramId)
    {
        $yearNow = date('Y');

        $query = Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->whereIn('year', [$yearNow, $yearNow - 1]);

        // Apply attributor conditions if they are provided
        if (!empty($attributorId)) {
            $query->where('attributor_id', $attributorId)
                ->where('attributor_particular_id', $attributorParticularId);
        }

        // Fetch allocation years
        $allocations = $query->pluck('year', 'year')->toArray();

        return empty($allocations) ? ['' => 'No Allocation Available.'] : $allocations;
    }


    protected static function getQualificationTitles($scholarshipProgramId, $tviId, $year)
    {
        $tvi = Tvi::with(['district.province'])->find($tviId);

        if (!$tvi || !$tvi->district || !$tvi->district->province) {
            return ['' => 'No Skill Priority available'];
        }

        $provinceId = $tvi->district->province->id;

        $institutionPrograms = $tvi->trainingPrograms()->pluck('training_programs.id')->toArray();

        if (empty($institutionPrograms)) {
            return ['' => 'No Training Programs available for this Institution.'];
        }

        $schoPro = ScholarshipProgram::where('id', $scholarshipProgramId)->first();
        if (!$schoPro) {
            return ['' => 'Invalid Scholarship Program.'];
        }

        $scholarshipPrograms = ScholarshipProgram::where('code', $schoPro->code)->pluck('id')->toArray();

        $qualificationTitlesQuery = QualificationTitle::whereIn('scholarship_program_id', $scholarshipPrograms)
            ->where('status_id', 1)
            ->where('soc', 1)
            ->whereNull('deleted_at')
            ->with('trainingProgram')
            ->get();

        if ($qualificationTitlesQuery->isEmpty()) {
            return ['' => 'No Qualification Titles available for the specified Scholarship Program.'];
        }

        $skillPriorities = SkillPriority::where('province_id', $provinceId)
            ->where('available_slots', '>=', 10)
            ->where('year', $year)
            ->with('trainingProgram')
            ->get();

        if ($skillPriorities->isEmpty()) {
            return ['' => 'No Skill Priorities available for the Province.'];
        }

        $qualifiedProgramIds = $skillPriorities->pluck('trainingProgram.*.id')->flatten()->unique()->toArray();

        $qualificationTitles = $qualificationTitlesQuery->filter(function ($qualification) use ($institutionPrograms, $qualifiedProgramIds) {
            return in_array($qualification->training_program_id, $institutionPrograms) && in_array($qualification->training_program_id, $qualifiedProgramIds);
        })->mapWithKeys(function ($qualification) {
            $title = $qualification->trainingProgram->title;

            if (preg_match('/\bNC\s+[I]{1,3}\b/i', $title)) {
                $title = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                    return 'NC ' . strtoupper($matches[1]);
                }, $title);
            }

            return [$qualification->id => "{$qualification->trainingProgram->soc_code} - {$qualification->trainingProgram->title} ({$qualification->scholarshipProgram->name})"];
        })->toArray();

        return !empty($qualificationTitles) ? $qualificationTitles : ['' => 'No Qualification Titles available'];
    }

    public function getFormattedParticularAttribute()
    {
        $particular = $this->allocation->particular ?? null;

        if (!$particular) {
            return 'No Particular Available';
        }

        $district = $particular->district;
        $municipality = $district ? $district->municipality : null;
        $province = $municipality ? $municipality->province : null;

        $districtName = $district ? $district->name : 'Unknown District';
        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
        $provinceName = $province ? $province->name : 'Unknown Province';

        $subParticular = $particular->subParticular->name ?? 'Unknown Sub-Particular';

        if ($subParticular === 'Partylist') {
            return "{$subParticular} - {$particular->partylist->name}";
        } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
            return "{$subParticular}";
        } else {
            return "{$subParticular} - {$districtName}, {$municipalityName}";
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
        return $this->$allocation->scholarship_program->name ?? 'No Scholarship Program Available';
    }
    protected function getFundSource($abddSectorsallocation)
    {
        $legislator = $this->$$abddSectorsallocation->legislator;

        if (!$legislator) {
            return 'No Legislator Available';
        }

        $particulars = $legislator->particular;

        if ($particulars->isEmpty()) {
            return 'No Particular Available';
        }

        $particular = $this->$abddSectorsallocation->particular;
        $subParticular = $particular->subParticular;
        $fundSource = $subParticular ? $subParticular->fundSource : null;

        return $fundSource ? $fundSource->name : 'No Fund Source Available';
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

    protected static function calculateCostPerSlot($record, $costProperty)
    {
        $totalCost = $record->{$costProperty};
        $slots = $record->number_of_slots;

        if ($slots > 0) {
            return $totalCost / $slots;
        }

        return 0;
    }

    private function formatCurrency($amount)
    {
        $formatter = new \NumberFormatter('en_PH', \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, 'PHP');
    }
}
