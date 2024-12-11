<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributionTargetResource\Pages;
use App\Models\Allocation;
use App\Models\DeliveryLearning;
use App\Models\DeliveryMode;
use App\Models\LearningMode;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SubParticular;
use App\Models\Target;
use App\Models\TargetStatus;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
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
                                                    ->toArray() ?: ['no_legislator' => 'No attributor available'];
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
                                            ->disabled()
                                            ->dehydrated()
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
                                                    ->toArray() ?: ['no_particular' => 'No particular available'];
                                                }
        
                                                return ['no_particular' => 'No particular available. Select an attributor first.'];
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
                                            ->disabled()
                                            ->dehydrated()
                                            ->reactive()
                                            ->live(),
                                        
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

                                                return $legislatorId
                                                    ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                                    : ['no_scholarship_program' => 'No scholarship program available. Select a particular first.'];
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
                                            ->disabled()
                                            ->dehydrated()
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
                                                $legislatorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');
                                                $scholarshipProgramId = $get('attribution_scholarship_program');

                                                return $legislatorId
                                                    ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                                    : ['no_allocation' => 'No appropriation year available. Select a scholarship program first.'];
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
                                            ->disabled()
                                            ->dehydrated()
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
                                                    : ['no_allocation' => 'No appropriation type available. Select an appropriation year first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_allocation')
                                            ->disabled()
                                            ->dehydrated()
                                            ->reactive()
                                            ->live(),
                                    ])
                                    ->columns(5),
                                    
                                Fieldset::make('Receiver')
                                    ->schema([
                                        TextInput::make('abscap_id')
                                            ->label('Absorbative Capacity ID')
                                            ->placeholder('Enter an Absorbative capacity ID')
                                            ->numeric(),
                                    
                                        Select::make('attribution_receiver')
                                            ->label('Legislator')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $attributor_id = $get('attribution_sender');
                                                return Legislator::where('status_id', 1)
                                                    ->whereNot('id', $attributor_id)
                                                    ->whereNull('deleted_at')
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_legislator' => 'No legislator available'];
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
                                            ->disabled()
                                            ->dehydrated()
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
                                            
                                                // If a legislator is selected, load their associated particulars
                                                if ($legislatorId) {
                                                    $particulars = Particular::whereHas('legislator', function ($query) use ($legislatorId) {
                                                        $query->where('legislator_particular.legislator_id', $legislatorId);
                                                    })
                                                    ->with('subParticular') // Load related subParticulars
                                                    ->get();
                                            
                                                    // Create an array of options, combining subParticular.name if available
                                                    $particularOptions = $particulars->mapWithKeys(function ($particular) {
                                                        // Check if subParticular exists and if its name is 'Party-list'
                                                        if ($particular->subParticular) {
                                                            if ($particular->subParticular->name === 'Party-list') {
                                                                $name = $particular->partylist->name;
                                                            }
                                                            elseif ($particular->subParticular->name === 'District') {
                                                                $name = $particular->district->name . ' - ' . $particular->district->province->name . ', ' .  $particular->district->province->region->name;
                                                            }

                                                            elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                                                                $name = $particular->subParticular->name. ' - ' .  $particular->district->province->region->name;
                                                            }
                                                            else {
                                                                $name = $particular->subParticular->name;
                                                            }
                                                        } else {
                                                            $name = $particular->name;
                                                        }
                                            
                                                        return [$particular->id => $name];
                                                    })->toArray();
                                            
                                                    // If only one particular is found, select it by default
                                                    if (count($particularOptions) === 1) {
                                                        $defaultParticularId = key($particularOptions);
                                                        $set('attribution_receiver_particular', $defaultParticularId);
                                                    }
                                            
                                                    return $particularOptions ?: ['no_particular' => 'No particular available'];
                                                }
                                            
                                                // Default message when no legislator is selected
                                                return ['no_particular' => 'No particular available. Select a legislator first.'];
                                            })                                            
                                            ->disableOptionWhen(fn($value) => $value === 'no_particular')
                                            ->disabled()
                                            ->dehydrated()
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
                                                $scholarshipProgramId = $get('attribution_scholarship_program');
        
                                                return $scholarshipProgramId
                                                    ? self::getQualificationTitles($scholarshipProgramId)
                                                    : ['no_qualification_title' => 'No qualification title available. Select a scholarship program first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

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
        
        
                                        Select::make('abdd_id')
                                            ->label('ABDD Sector')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $tviId = $get('tvi_id');
        
                                                return $tviId
                                                    ? self::getAbddSectors($tviId)
                                                    : ['no_abdd' => 'No ABDD sector available. Select an institution first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_abdd'),

                                         TextInput::make('admin_cost')
                                            ->label('Admin Cost')
                                            ->placeholder('Enter amount of Admin Cost')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->autocomplete(false)
                                            ->numeric(),
                                            
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
                                                $houseSpeakerIds = SubParticular::whereIn('name', ['House Speaker', 'House Speaker (LAKAS)'])
                                                    ->pluck('id');

                                                return Legislator::where('status_id', 1)
                                                    ->whereNull('deleted_at')
                                                    ->has('allocation')
                                                    ->whereHas('particular', function ($query) use ($houseSpeakerIds) {
                                                        $query->whereIn('sub_particular_id', $houseSpeakerIds);
                                                    })
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_legislator' => 'No attributor available'];
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
                                                    ->toArray() ?: ['no_particular' => 'No particular available'];
                                                }
        
                                                return ['no_particular' => 'No particular available. Select an attributor first.'];
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
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislatorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');

                                                return $legislatorId
                                                    ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                                    : ['no_scholarship_program' => 'No scholarship program available. Select a particular first.'];
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
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $legislatorId = $get('attribution_sender');
                                                $particularId = $get('attribution_sender_particular');
                                                $scholarshipProgramId = $get('attribution_scholarship_program');

                                                return $legislatorId
                                                    ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                                    : ['no_allocation' => 'No appropriation year available. Select a scholarship program first.'];
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
                                                    : ['no_allocation' => 'No appropriation type available. Select an appropriation year first.'];
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
                                            ->placeholder('Enter an Absorbative capacity ID')
                                            ->numeric(),
                                    
                                            Select::make('attribution_receiver')
                                            ->label('Legislator')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->preload()
                                            ->searchable()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $attributor_id = $get('attribution_sender');
                                                return Legislator::where('status_id', 1)
                                                    ->whereNot('id', $attributor_id)
                                                    ->whereNull('deleted_at')
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_legislator' => 'No legislator available'];
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
                                                            }
                                                            elseif ($particular->subParticular->name === 'District') {
                                                                $name = $particular->district->name . ' - ' . $particular->district->province->name . ', ' .  $particular->district->province->region->name;
                                                            }

                                                            elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                                                                $name = $particular->subParticular->name. ' - ' .  $particular->district->province->region->name;
                                                            }
                                                            else {
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
                                            
                                                    return $particularOptions ?: ['no_particular' => 'No particular available'];
                                                }
                                            
                                                return ['no_particular' => 'No particular available. Select a legislator first.'];
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
                                                $scholarshipProgramId = $get('attribution_scholarship_program');
        
                                                return $scholarshipProgramId
                                                    ? self::getQualificationTitles($scholarshipProgramId)
                                                    : ['no_qualification_title' => 'No qualification title available. Select a scholarship program first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_qualification_title'),

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
        
                                        Select::make('abdd_id')
                                            ->label('ABDD Sector')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->options(function ($get) {
                                                $tviId = $get('tvi_id');
        
                                                return $tviId
                                                    ? self::getAbddSectors($tviId)
                                                    : ['no_abdd' => 'No ABDD sector available. Select an institution first.'];
                                            })
                                            ->disableOptionWhen(fn($value) => $value === 'no_abdd'),

                                         TextInput::make('admin_cost')
                                            ->label('Admin Cost')
                                            ->placeholder('Enter amount of Admin Cost')
                                            ->required()
                                            ->markAsRequired(false)
                                            ->autocomplete(false)
                                            ->numeric(),
                                            
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
                            ])
                            ->maxItems(100)
                            ->columns(5)
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
            ->emptyStateHeading('No attribution targets available')
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

                
                TextColumn::make('attributionAllocation.legislator.name')
                    ->label('Attribution Sender')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('attributionAllocation.legislator.particular.subParticular')
                    ->label('Attribution Particular')
                    ->searchable()
                    ->toggleable()
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
                    ->toggleable(),

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

                TextColumn::make('qualification_title_name')
                    ->label('Qualification Title')
                    ->searchable()
                    ->toggleable(),

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
                    ->label('Learning Mode')
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
                    ->toggleable(),
            ])
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
                        ->icon('heroicon-o-magnifying-glass')
                        ->url(fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id])),
                    
                    Action::make('viewComment')
                        ->label('View Comments')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->url(fn($record) => route('filament.admin.resources.targets.showComments', ['record' => $record->id])),
                    
                    Action::make('setAsCompliant')
                        ->label('Set as Compliant')
                        ->icon('heroicon-o-check-circle')
                        ->url(fn($record) => route('filament.admin.resources.compliant-targets.create', ['record' => $record->id])),
                    
                    Action::make('setAsNonCompliant')
                        ->label('Set as Non-Compliant')
                        ->icon('heroicon-o-x-circle')
                        ->url(fn($record) => route('filament.admin.resources.non-compliant-targets.create', ['record' => $record->id])),
                    
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Target has been deleted successfully.');
                        }),

                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

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
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected targets have been deleted successfully.');
                        }),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected targets have been restored successfully.');
                        }),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected targets have been deleted permanently.');
                        }),
                ]),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id])
            );
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
            ->toArray() ?: ['no_qualification_title' => 'No qualification title available'];
    }

    protected static function getAbddSectors($tviId)
    {
        $tvi = Tvi::with(['district.municipality.province'])->find($tviId);

        if (!$tvi || !$tvi->district || !$tvi->district || !$tvi->district->province) {
            return ['no_abdd' => 'No ABDD sector available'];
        }

        return $tvi->district->province->abdds()
            ->select('abdds.id', 'abdds.name')
            ->pluck('name', 'id')
            ->toArray() ?: ['no_abdd' => 'No ABDD sector available'];
    }

    public static function getEloquentQuery(): Builder
    {
        $pendingStatus = TargetStatus::where('desc', 'Pending')->first();

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNot('attribution_allocation_id', null)
            ->where('target_status_id', $pendingStatus->id);
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