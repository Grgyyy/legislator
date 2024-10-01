<?php

namespace App\Filament\Resources;

use App\Models\Tvi;
use App\Models\Abdd;
use App\Models\Target;
use Filament\Forms\Form;
use App\Models\Allocation;
use App\Models\FundSource;
use App\Models\Legislator;
use App\Models\Particular;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
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
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema(function ($record) {
            // Check if we're editing an existing record
            if ($record) {
                return [
                    Select::make('legislator_id')
                        ->label('Legislator Name')
                        ->required()
                        ->searchable()
                        ->options(function () {
                            $legislators = Legislator::where('status_id', 1)
                                ->whereNull('deleted_at')
                                ->has('allocation')
                                ->pluck('name', 'id')
                                ->toArray();

                            return empty($legislators) ? ['' => 'No Legislator Available.'] : $legislators;
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('particular_id', null);
                        }),

                    Select::make('particular_id')
                        ->label('Particular')
                        ->required()
                        ->searchable()
                        ->options(function ($get) {
                            $legislatorId = $get('legislator_id');
                            return $legislatorId ? self::getParticularOptions($legislatorId) : ['' => 'No Particular Available.'];
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('scholarship_program_id', null);
                            $set('qualification_title_id', null);
                        }),

                    Select::make('scholarship_program_id')
                        ->label('Scholarship Program')
                        ->required()
                        ->searchable()
                        ->options(function ($get) {
                            $legislatorId = $get('legislator_id');
                            $particularId = $get('particular_id');
                            return $legislatorId ? self::getScholarshipProgramsOptions($legislatorId, $particularId) : ['' => 'No Scholarship Program Available.'];
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('allocation_year', null);
                            $set('qualification_title_id', null);
                        }),

                    Select::make('allocation_year')
                        ->label('Appropriation Year')
                        ->required()
                        ->searchable()
                        ->options(function ($get) {
                            $legislatorId = $get('legislator_id');
                            $particularId = $get('particular_id');
                            $scholarshipProgramId = $get('scholarship_program_id');
                            return $legislatorId && $particularId && $scholarshipProgramId
                                ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                : ['' => 'No Allocation Available.'];
                        }),

                    Select::make('appropriation_type')
                        ->label('Allocation Type')
                        ->required()
                        ->options([
                            'Current' => 'Current',
                            'Continuing' => 'Continuing',
                        ]),

                    Select::make('tvi_id')
                        ->label('Institution')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->relationship('tvi', 'name'),

                    Select::make('qualification_title_id')
                        ->label('Qualification Title')
                        ->required()
                        ->searchable()
                        ->options(function ($get) {
                            $scholarshipProgramId = $get('scholarship_program_id');
                            return $scholarshipProgramId ? self::getQualificationTitles($scholarshipProgramId) : ['' => 'No Qualification Title Available.'];
                        }),

                    Select::make('abdd_id')
                        ->label('ABDD Sector')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function ($get) {
                            $tviId = $get('tvi_id');
                            return $tviId ? self::getAbddSectors($tviId) : ['' => 'No ABDD Sector Available.'];
                        }),

                    TextInput::make('number_of_slots')
                        ->label('Number of Slots')
                        ->required()
                        ->numeric(),
                ];
            } else {
                // Create form with repeater
                return [
                    Repeater::make('targets')
                        ->schema([
                            Select::make('legislator_id')
                                ->label('Legislator Name')
                                ->required()
                                ->searchable()
                                ->options(function () {
                                    $legislators = Legislator::where('status_id', 1)
                                        ->whereNull('deleted_at')
                                        ->has('allocation')
                                        ->pluck('name', 'id')
                                        ->toArray();

                                    return empty($legislators) ? ['' => 'No Legislator Available.'] : $legislators;
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('particular_id', null);
                                }),

                        Select::make('particular_id')
                            ->label('Particular')
                            ->required()
                            ->markAsRequired(false)
                            ->preload()
                            ->searchable()
                            ->options(function ($get) {
                                $legislatorId = $get('legislator_id');
                                return $legislatorId ? self::getParticularOptions($legislatorId) : ['' => 'No Particular Available.'];
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset the 'particular_id' field to null when legislator is changed,
                                // because the available particular options will change.
                                $set('scholarship_program_id', null);
        
                                // Fetch new particular options based on the selected legislator ($state contains legislator_id).
                                $scholarshipPrograms = self::getScholarshipProgramsOptions($state, $state);
                        
                                // Update the 'particularOptions' state with the new options so that the 'particular_id' dropdown
                                // can display the correct options based on the selected legislator.
                                $set('scholarshipProgramsOptions', $scholarshipPrograms);
                        
                                // If there's only one particular available, automatically select it.
                                if (count($scholarshipPrograms) === 1) {
                                    // Auto-select the only available particular by setting 'particular_id'
                                    // to the key of the single option.
                                    $set('scholarship_program_id', key($scholarshipPrograms));
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset the 'particular_id' field to null when legislator is changed,
                                // because the available particular options will change.
                                $set('allocation_year', null);
        
                                // Fetch new particular options based on the selected legislator ($state contains legislator_id).
                                $year = self::getAllocationYear($state, $state, $state);
                        
                                // Update the 'particularOptions' state with the new options so that the 'particular_id' dropdown
                                // can display the correct options based on the selected legislator.
                                $set('allocationYear', $year);
                        
                                // If there's only one particular available, automatically select it.
                                if (count($year) === 1) {
                                    // Auto-select the only available particular by setting 'particular_id'
                                    // to the key of the single option.
                                    $set('allocation_year', key($year));
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset the 'particular_id' field to null when legislator is changed,
                                // because the available particular options will change.
                                $set('appropriation_type', null);
        
                                // Fetch new particular options based on the selected legislator ($state contains legislator_id).
                                $appropriationType = self::getAppropriationTypeOptions($state);
                        
                                // Update the 'particularOptions' state with the new options so that the 'particular_id' dropdown
                                // can display the correct options based on the selected legislator.
                                $set('appropriationType', $appropriationType);

                        
                                // // If there's only one particular available, automatically select it.
                                if (count($appropriationType) === 1) {
                                    // Auto-select the only available particular by setting 'particular_id'
                                    // to the key of the single option.
                                    $set('appropriation_type', key($appropriationType));
                                }
                            })
                            ->reactive()
                            ->live()
                            ->native(false)
                            ->disableOptionWhen(fn ($value) => $value === 'no_legislator'),

                        Select::make('scholarship_program_id')
                            ->label('Scholarship Program')
                            ->required()
                            ->searchable()
                            ->options(function ($get) {
                                $legislatorId = $get('legislator_id');
                                $particularId = $get('particular_id');
                                return $legislatorId ? self::getScholarshipProgramsOptions($legislatorId, $particularId) : ['' => 'No Scholarship Program Available.'];
                            })
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset the 'particular_id' field to null when legislator is changed,
                                // because the available particular options will change.
                                $set('allocation_year', null);
        
                                // Fetch new particular options based on the selected legislator ($state contains legislator_id).
                                $year = self::getAllocationYear($state, $state, $state);
                        
                                // Update the 'particularOptions' state with the new options so that the 'particular_id' dropdown
                                // can display the correct options based on the selected legislator.
                                $set('allocationYear', $year);
                        
                                // If there's only one particular available, automatically select it.
                                if (count($year) === 1) {
                                    // Auto-select the only available particular by setting 'particular_id'
                                    // to the key of the single option.
                                    $set('allocation_year', key($year));
                                }
                            }),

                            Select::make('allocation_year')
                                ->label('Appropriation Year')
                                ->required()
                                ->searchable()
                                ->reactive()
                            ->live()
                            ->options(function ($get) {
                                    $legislatorId = $get('legislator_id');
                                    $particularId = $get('particular_id');
                                    $scholarshipProgramId = $get('scholarship_program_id');
                                    return $legislatorId && $particularId && $scholarshipProgramId
                                        ? self::getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
                                        : ['' => 'No Allocation Available.'];
                                })
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset the 'particular_id' field to null when legislator is changed,
                                // because the available particular options will change.
                                $set('appropriation_type', null);
        
                                // Fetch new particular options based on the selected legislator ($state contains legislator_id).
                                $appropriationType = self::getAppropriationTypeOptions($state);
                        
                                // Update the 'particularOptions' state with the new options so that the 'particular_id' dropdown
                                // can display the correct options based on the selected legislator.
                                $set('appropriationType', $appropriationType);

                        
                                // // If there's only one particular available, automatically select it.
                                if (count($appropriationType) === 1) {
                                    // Auto-select the only available particular by setting 'particular_id'
                                    // to the key of the single option.
                                    $set('appropriation_type', key($appropriationType));
                                }
                            }),

                        Select::make('appropriation_type')
                            ->label('Allocation Type')
                            ->required()
                            ->options(function ($get) {
                                $year = $get('allocation_year');
                                return self::getAppropriationTypeOptions($year);
                            })
                            ->reactive()
                            ->live(),
                            

                            Select::make('tvi_id')
                                ->label('Institution')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->relationship('tvi', 'name'),

                            Select::make('qualification_title_id')
                                ->label('Qualification Title')
                                ->required()
                                ->searchable()
                                ->options(function ($get) {
                                    $scholarshipProgramId = $get('scholarship_program_id');
                                    return $scholarshipProgramId ? self::getQualificationTitles($scholarshipProgramId) : ['' => 'No Qualification Title Available.'];
                                }),

                            Select::make('abdd_id')
                                ->label('ABDD Sector')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(function ($get) {
                                    $tviId = $get('tvi_id');
                                    return $tviId ? self::getAbddSectors($tviId) : ['' => 'No ABDD Sector Available.'];
                                }),

                            TextInput::make('number_of_slots')
                                ->label('Number of Slots')
                                ->required()
                                ->numeric(),
                        ])
                        ->columns(4)
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
            ->emptyStateHeading('No targets yet')
            ->columns([

                TextColumn::make('fund_source')
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
                    })
                    ->searchable()
                    ->toggleable()
                    ->label('Fund Source'),
                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Soft/Commitment')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('appropriation_type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.year')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.legislator.particular.subParticular')
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
                            if ($particular->subParticular && $particular->subParticular->name === 'Partylist') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else {
                                return $particular->subParticular->name ?? 'Unknown SubParticular';
                            }
                        } else {
                            return "{$particular->subParticular->name} - {$districtName}, {$municipalityName}";
                        }
                    })
                    ->searchable()
                    ->toggleable()
                    ->label('Particular'),
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
                    ->searchable()
                    ->toggleable()
                    ->label('Institution'),
                TextColumn::make('tvi.tviClass.tviType.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.tviClass.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('qualification_title.training_program.title')
                    ->label('Qualification Title')
                    ->getStateUsing(function ($record) {
                        $qualificationTitle = $record->qualification_title;

                        if (!$qualificationTitle) {
                            return 'No Qualification Title Available';
                        }

                        $trainingProgram = $qualificationTitle->trainingProgram;

                        return $trainingProgram ? $trainingProgram->title : 'No Training Program Available';
                    }),
                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program'),
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
                    ->searchable()
                    ->toggleable()
                    ->label('Status'),
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
                        ->url(fn($record) => route('filament.admin.resources.targets.showComments', ['record' => $record->id]))
                        ->icon('heroicon-o-check-circle'),
                        Action::make('setAsNonCompliant')
                        ->label('Set as Non-Compliant')
                        ->url(fn($record) => route('filament.admin.resources.non-compliant-remarks.create', ['record' => $record->id]))
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
                    ExportBulkAction::make()->exports([
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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->orderBy('updated_at', 'desc');
    }

    protected static function getParticularOptions($legislatorId)
    {
        $particulars = Particular::whereHas('allocation', function ($query) use ($legislatorId) {
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
            ->toArray();

        return empty($particulars) ? ['' => 'No Particular Available'] : $particulars;
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

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId)
    {
        $scholarshipPrograms = ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
            $query->where('legislator_id', $legislatorId)
                ->where('particular_id', $particularId);
        })->pluck('name', 'id')->toArray();

        return empty($scholarshipPrograms) ? ['' => 'No Scholarship Program Available'] : $scholarshipPrograms;
    }

    protected static function getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
    {
        $yearNow = date('Y');
        $allocations = Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->whereIn('year', [$yearNow, $yearNow - 1])
            ->pluck('year', 'year')
            ->toArray();

        return empty($allocations) ? ['' => 'No Allocation Available.'] : $allocations;
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







}
