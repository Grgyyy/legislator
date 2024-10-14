<?php

namespace App\Filament\Resources;

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
                            ->label('Legislator')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function () {
                                return Legislator::where('status_id', 1)
                                    ->whereNull('deleted_at')
                                    ->has('allocation')
                                    ->pluck('name', 'id')
                                    ->toArray() ?: ['no_legislators' => 'No Legislator Available'];
                            })
                            ->dehydrated()
                            ->disabled(),

                        Select::make('particular_id')
                            ->label('Particular')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $legislatorId = $get('legislator_id');

                                return $legislatorId
                                    ? self::getParticularOptions($legislatorId)
                                    : ['no_particular' => 'No Particular Available.'];
                            })
                            ->dehydrated()
                            ->disabled(),

                        Select::make('scholarship_program_id')
                            ->label('Scholarship Program')
                            ->required()
                            ->markAsRequired(false)
                            ->options(function ($get) {
                                $legislatorId = $get('legislator_id');
                                $particularId = $get('particular_id');

                                return $legislatorId
                                    ? self::getScholarshipProgramsOptions($legislatorId, $particularId)
                                    : ['no_scholarship_program' => 'No Scholarship Program Available.'];
                            })
                            ->dehydrated()
                            ->disabled(),

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
                                    : ['no_allocation' => 'No Allocation Available.'];
                            })
                            ->dehydrated()
                            ->disabled(),

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
                                    ->toArray() ?: ['no_tvi' => 'No Institution Available'];
                            })
                            ->disableOptionWhen(fn ($value) => $value === 'no_tvi'),

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
                                    : ['no_qualification_title' => 'No Qualification Title Available.'];
                            })
                            ->disableOptionWhen(fn ($value) => $value === 'no_qualification_title'),

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
                                    : ['no_abddd' => 'No ABDD Sector Available'];
                            })
                            ->disableOptionWhen(fn ($value) => $value === 'no_abddd'),

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
                            ])
                    ];
                } else {
                    return [
                        Repeater::make('targets')
                            ->schema([
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
                                            ->has('allocation')
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_legislators' => 'No Legislator Available'];
                                    })
                                    ->disableOptionWhen(fn ($value) => $value === 'no_legislators')
                                    ->live()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (!$state) {
                                            $set('particular_id', null);
                                            $set('scholarship_program_id', null);
                                            $set('allocation_year', null);
                                            $set('appropriation_type', null);
                                            $set('particularOptions', []);
                                            $set('scholarshipProgramOptions', []);
                                            $set('appropriationYearOptions', []);
                                            return;
                                        }

                                        $allocations = Allocation::where('legislator_id', $state)
                                            ->with('particular', 'scholarship_program')
                                            ->get();

                                        $particularOptions = $allocations->pluck('particular.name', 'particular.id')->toArray();
                                        $scholarshipProgramOptions = $allocations->pluck('scholarship_program.name', 'scholarship_program.id')->toArray();
                                        $appropriationYearOptions = $allocations->pluck('year', 'year')->toArray();

                                        $set('particularOptions', $particularOptions);
                                        $set('scholarshipProgramOptions', $scholarshipProgramOptions);
                                        $set('appropriationYearOptions', $appropriationYearOptions);

                                        $currentYear = now()->year;

                                        if (count($particularOptions) === 1) {
                                            $set('particular_id', key($particularOptions));
                                        }

                                        if (count($scholarshipProgramOptions) === 1) {
                                            $set('scholarship_program_id', key($scholarshipProgramOptions));
                                        }

                                        if (count($appropriationYearOptions) === 1) {
                                            $set('allocation_year', key($appropriationYearOptions));

                                            if (key($appropriationYearOptions) == $currentYear) {
                                                $set('appropriation_type', 'Current');
                                            }
                                        }
                                    }),

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
                                            ? self::getParticularOptions($legislatorId)
                                            : ['no_particular' => 'No Particular Available'];
                                    })
                                    ->disableOptionWhen(fn ($value) => $value === 'no_particular')
                                    ->reactive()
                                    ->live()
                                    // ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    //     // Fetch all allocations based on the selected legislator and particular
                                    //     $legislatorId = $get('legislator_id');

                                    //     $allocations = Allocation::where('legislator_id', $legislatorId)
                                    //         ->where('particular_id', $state)
                                    //         ->with('scholarship_program') // Load the related scholarship programs
                                    //         ->get();

                                    //     // Prepare options for the scholarship_program_id field
                                    //     $scholarshipOptions = $allocations->pluck('scholarship_program.name', 'scholarship_program.id')->toArray();

                                    //     // Set the scholarship_program_id field options
                                    //     $set('scholarship_program_id_options', $scholarshipOptions);

                                    //     // If only one scholarship program is found, set it as the selected value
                                    //     if (count($scholarshipOptions) === 1) {
                                    //         $set('scholarship_program_id', key($scholarshipOptions));
                                    //     }
                                    // })
                                    ,


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
                                        : ['no_scholarship_program' => 'No Scholarship Program Available'];
                                })
                                ->disableOptionWhen(fn ($value) => $value === 'no_scholarship_program')
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('allocation_year', null);
                                    $set('appropriation_type', null);

                                    $year = self::getAllocationYear($state, $state, $state);
                                    $appropriationType = self::getAppropriationTypeOptions($state);

                                    $set('allocationYear', $year);
                                    $set('appropriationType', $appropriationType);

                                    if (count($year) === 1) {
                                        $set('allocation_year', key($year));
                                    }

                                    if (count($appropriationType) === 1) {
                                        $set('appropriation_type', key($appropriationType));
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
                                    $legislatorId = $get('legislator_id');
                                    $particularId = $get('particular_id');
                                    $scholarshipProgramId = $get('scholarship_program_id');

                                    return $legislatorId
                                        ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                        : ['no_allocation' => 'No Allocation Available.'];
                                })
                                ->disableOptionWhen(fn ($value) => $value === 'no_allocation')
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
                                ->label('Appropriation Type')
                                ->required()
                                ->markAsRequired(false)
                                ->preload()
                                ->searchable()
                                ->native(false)
                                ->options(function ($get) {
                                    $year = $get('allocation_year');

                                    return $year
                                        ? self::getAppropriationTypeOptions($year)
                                        : ['no_allocation' => 'No Allocation Available.'];
                                })
                                ->disableOptionWhen(fn ($value) => $value === 'no_allocation')
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
                                        ->toArray() ?: ['no_tvi' => 'No Institution Available'];
                                })
                                ->disableOptionWhen(fn ($value) => $value === 'no_tvi'),

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
                                        : ['no_qualification_title' => 'No Qualification Title Available.'];
                                })
                                ->disableOptionWhen(fn ($value) => $value === 'no_qualification_title'),

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
                                        : ['no_abddd' => 'No ABDD Sector Available'];
                                })
                                ->disableOptionWhen(fn ($value) => $value === 'no_abddd'),

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
                                ])
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
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No Legislator Available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No Particular Available';
                        }

                        $particular = $record->allocation->particular;
                        $subParticular = $particular->subParticular;
                        $fundSource = $subParticular ? $subParticular->fundSource : null;

                        return $fundSource ? $fundSource->name : 'No Fund Source Available';
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
                            return 'No Legislator Available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No Particular Available';
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
                            return 'No Qualification Title Available';
                        }

                        $trainingProgram = $qualificationTitle->trainingProgram;

                        return $trainingProgram ? $trainingProgram->title : 'No Training Program Available';
                    }),

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
                                                return 'No Legislator Available';
                                            }

                                            $particulars = $legislator->particular;

                                            if ($particulars->isEmpty()) {
                                                return 'No Particular Available';
                                            }

                                            $particular = $record->allocation->particular;
                                            $subParticular = $particular->subParticular;
                                            $fundSource = $subParticular ? $subParticular->fundSource : null;

                                            return $fundSource ? $fundSource->name : 'No Fund Source Available';
                                        }),
                                    Column::make('allocation.legislator.name')
                                        ->heading('Legislator'),
                                    Column::make('allocation.soft_or_commitment')
                                        ->heading('Soft or Commitment'),
                                    Column::make('appropriation_type')
                                        ->heading('Appropriation Type'),
                                    Column::make('allocation.year')
                                        ->heading('Allocation'),
                                    Column::make('formatted_particular')
                                        ->heading('Particular'),
                                    Column::make('tvi.district.name')
                                        ->heading('District'),
                                    Column::make('tvi.district.municipality.name')
                                        ->heading('Municipality'),
                                    Column::make('tvi.district.municipality.province.name')
                                        ->heading('Province'),
                                    Column::make('tvi.district.municipality.province.region.name')
                                        ->heading('Region'),
                                    Column::make('tvi.name')
                                        ->heading('Institution'),
                                    Column::make('tvi.tviClass.tviType.name')
                                        ->heading('TVI Type'),
                                    Column::make('tvi.tviClass.name')
                                        ->heading('TVI Class'),
                                    Column::make('qualification_title.training_program.title')
                                        ->heading('Qualification Title')
                                        ->getStateUsing(function ($record) {
                                            $qualificationTitle = $record->qualification_title;

                                            $trainingProgram = $qualificationTitle->trainingProgram;

                                            return $trainingProgram ? $trainingProgram->title : 'No Training Program Available';
                                        }),
                                    Column::make('allocation.scholarship_program.name')
                                        ->heading('Scholarship Program'),
                                    Column::make('number_of_slots')
                                        ->heading('No. of slots'),
                                    Column::make('qualification_title.pcc')
                                        ->heading('Per Capita Cost')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('total_amount')
                                        ->heading('Total amount')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('targetStatus.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Targets')
                        ]),
                    ]),
            ]);
    }

    protected static function getParticularOptions($legislatorId) {
        return Particular::whereHas('allocation', function($query) use ($legislatorId) {
            $query->where('legislator_id', $legislatorId);
        })
            ->with('subParticular')
            ->get()
            ->mapWithKeys(function ($particular) {

                if ($particular->district->name === 'Not Applicable') {
                    if ($particular->subParticular->name === 'Partylist') {
                        return [$particular->id => $particular->subParticular->name . " - " . $particular->partylist->name  ];
                    }
                    else {
                        return [$particular->id => $particular->subParticular->name ];
                    }
                }
                else {
                    return [$particular->id => $particular->subParticular->name . " - "  . $particular->district->name . ', ' . $particular->district->municipality->name];
                }

            })
            ->toArray() ?: ['no_particular' => 'No Particular Available'];
    }

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId) {
        return ScholarshipProgram::whereHas('allocation', function($query) use ($legislatorId, $particularId) {
            $query->where('legislator_id', $legislatorId)
                ->where('particular_id', $particularId);
        })
            ->pluck('name', 'id')
            ->toArray() ?: ['no_scholarship_program' => 'No Scholarship Program Available'];
    }

    protected static function getAllocationYear($legislatorId, $particularId, $scholarshipProgramId) {
        $yearNow = date('Y');

        return Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->whereIn('year', [$yearNow, $yearNow - 1])
            ->pluck('year', 'year')
            ->toArray() ?: ['no_allocation' => 'No Allocation Available.'];
    }

    protected static function getAppropriationTypeOptions($year) {
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
            return ['' => 'No ABDD Sectors Available.'];
        }

        $abddSectors = $tvi->district->municipality->province->abdds()
            ->select('abdds.id', 'abdds.name')
            ->pluck('name', 'id')
            ->toArray();

        return empty($abddSectors) ? ['' => 'No ABDD Sectors Available.'] : $abddSectors;
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

        if ($subParticular === 'Party-list') {
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
