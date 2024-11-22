<?php

namespace App\Filament\Resources;

use App\Models\Target;
use App\Models\Allocation;
use App\Models\ScholarshipProgram;
use App\Models\QualificationTitle;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\SubParticular;
use App\Models\Tvi;
use App\Filament\Resources\AttributionTargetResource\Pages;
use App\Services\NotificationHandler;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Actions\Action;


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
                        Select::make('attribution_sender')
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
                                    ->disabled()
                                    ->dehydrated()
                                    ->searchable(),

                        Select::make('attribution_sender_particular')
                            ->label('Sender Particular')
                            ->options(function ($get) {
                                $legislatorId = $get('attribution_sender');

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
                            ->disabled()
                            ->dehydrated()
                            ->searchable(),
                        
                        Select::make('attribution_scholarship_program')
                            ->label('Sender Scholarship Program')
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
                                    $set('appropriation_type', null);
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
                                        $set('appropriation_type', 'Current');
                                    }
                                } else {
                                    $set('allocation_year', null);
                                    $set('appropriation_type', null);
                                }
                            })
                            ->reactive()
                            ->disabled()
                            ->dehydrated()
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
                                    : ['no_allocation' => 'No appropriation year available. Select a scholarship program first'];
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
                            ->disabled()
                            ->dehydrated()
                            ->live(),
                        
                        Select::make('attribution_appropriation_type')
                            ->label('Allocation Type')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                return ([
                                    "Current" => "Current",
                                    "Continuing" => "Continuing"
                                ]);
                            })
                            ->reactive()
                            ->disabled()
                            ->dehydrated()
                            ->live(),

                        Select::make('attribution_receiver')
                            ->label('Attribution Receiver')
                            ->options(function () {
                                $legislators = Legislator::where('status_id', 1)
                                    ->whereNull('deleted_at')
                                    ->pluck('name', 'id')
                                    ->toArray();

                                return !empty($legislators) ? $legislators : ['no_legislators' => 'No legislator available'];
                            })
                            ->disabled()
                            ->dehydrated()
                            ->searchable(),

                        Select::make('attribution_receiver_particular')
                            ->label('Receiver Particular')
                            ->options(function ($get) {
                                $legislatorId = $get('attribution_receiver');

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
                            ->disabled()
                            ->dehydrated()
                            ->searchable(),

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
                                Select::make('attribution_sender')
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

                                Select::make('attribution_sender_particular')
                                    ->label('Sender Particular')
                                    ->options(function ($get) {
                                        $legislatorId = $get('attribution_sender');

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
                                
                                Select::make('attribution_scholarship_program')
                                    ->label('Sender Scholarship Program')
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
                                            $set('appropriation_type', null);
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
                                            $legislatorId = $get('attribution_sender');
                                            $particularId = $get('attribution_sender_particular');
                                            $scholarshipProgramId = $get('attribution_scholarship_program');

                                            return $legislatorId
                                                ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                                : ['no_allocation' => 'No appropriation year available. Select a scholarship program first'];
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
                                        ->label('Allocation Type')
                                        ->required()
                                        ->markAsRequired(false)
                                        ->options(function ($get) {
                                            return ([
                                                "Current" => "Current",
                                                "Continuing" => "Continuing"
                                            ]);
                                        })
                                        ->reactive()
                                        ->live(),

                                    Select::make('attribution_receiver')
                                        ->label('Attribution Receiver')
                                        ->options(function () {
                                            $legislators = Legislator::where('status_id', 1)
                                                ->whereNull('deleted_at')
                                                ->pluck('name', 'id')
                                                ->toArray();
    
                                            return !empty($legislators) ? $legislators : ['no_legislators' => 'No legislator available'];
                                        })
                                        ->searchable(),

                                    Select::make('attribution_receiver_particular')
                                        ->label('Receiver Particular')
                                        ->options(function ($get) {
                                            $legislatorId = $get('attribution_receiver');
    
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
                    ];
                }
            });
    }

    public static function table(Table $table): Table
    {
        return $table
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
                
                TextColumn::make('attributionAllocation.legislator.name')
                    ->label('Legislator I')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.legislator.name')
                    ->label('Legislator II')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ? $state : '-'),
                
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
                    ->searchable()
                    ->toggleable(),

                    TextColumn::make('tvi.district.municipality.name')
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

                    TextColumn::make('qualification_title.training_program.title')
                        ->label('Qualification Title')
                        ->searchable()
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
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id])
            );
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
            'index' => Pages\ListAttributionTargets::route('/'),
            'create' => Pages\CreateAttributionTarget::route('/create'),
            'edit' => Pages\EditAttributionTarget::route('/{record}/edit'),
        ];
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNot('attribution_allocation_id', null);


        return $query;
    }
}
