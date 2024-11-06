<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributionTargetResource\Pages;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\SubParticular;
use App\Models\Target;
use App\Models\Tvi;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttributionTargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Attribution Targets";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;


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
                                return Tvi::whereNot('name', 'Not Applicable')
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
                        Select::make('legislator_id')
                            ->label('Attribution Receiver')
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
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
}
