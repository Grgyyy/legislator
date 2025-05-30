<?php

namespace App\Filament\Resources;

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
use Filament\Forms\Components\Section;
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

class CompliantTargetsResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Compliant Targets";

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

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
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray() ?: ['no_legislator' => 'No attributors available'];
                                })
                                ->disabled()
                                ->dehydrated()
                                ->validationAttribute('attributor'),

                            Select::make('attribution_sender_particular')
                                ->label('Particular')
                                ->default($record->allocation->attributor_particular_id ?? null)
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
                                ->default($record ? $record->allocation->scholarship_program_id : null)
                                ->options(function ($get) {
                                    $legislatorId = $get('attribution_sender');
                                    $particularId = $get('attribution_sender_particular');

                                    if ($legislatorId) {
                                        return ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
                                            $query->where('attributor_id', $legislatorId)
                                                ->when($particularId, fn($q) => $q->where('attributor_particular_id', $particularId));
                                        })
                                            ->pluck('name', 'id')
                                            ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                                    }

                                    return ScholarshipProgram::pluck('name', 'id')->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available. Select an Attributor and Particular first.'];
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
                                ->default($record ? $record->allocation->legislator_id : null)
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
                                    } else {
                                        $scholarshipProgramId = $get('attribution_scholarship_program');

                                        $allocations = Allocation::where('scholarship_program_id', $scholarshipProgramId)
                                            ->with('legislator')
                                            ->get()
                                            ->pluck('legislator.name', 'legislator.id')
                                            ->toArray();

                                        return $allocations ?? ['no_legislator' => 'No legislators available'];
                                    }
                                })
                                ->disabled()
                                ->dehydrated()
                                ->validationAttribute('legislator'),

                            Select::make('attribution_receiver_particular')
                                ->label('Particular')
                                ->required()
                                ->markAsRequired(false)
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
                                ->default($record ? $record->allocation->year : null)
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
                                ->disabled()
                                ->dehydrated()
                                ->validationAttribute('appropriation year'),

                            Select::make('attribution_appropriation_type')
                                ->label('Appropriation Type')
                                ->required()
                                ->markAsRequired(false)
                                ->default($record ? $record->appropriation_type : null)
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
                                ->default($record ? $record->tvi_id : null)
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
                                ->disabled()
                                ->dehydrated()
                                ->validationAttribute('institution'),

                            Select::make('qualification_title_id')
                                ->label('Qualification Title')
                                ->required()
                                ->markAsRequired(false)
                                ->default($record ? $record->qualification_title_id : null)
                                ->options(function ($get) {
                                    $scholarshipProgramId = $get('attribution_scholarship_program');
                                    $tviId = $get('tvi_id');
                                    $year = $get('allocation_year');

                                    return $scholarshipProgramId
                                        ? self::getQualificationTitles($scholarshipProgramId, $tviId, $year)
                                        : ['no_qualification_title' => 'No qualification titles available. Select a scholarship program first.'];
                                })
                                ->disabled()
                                ->dehydrated()
                                ->validationAttribute('qualification title'),

                            Select::make('abdd_id')
                                ->label('ABDD Sector')
                                ->required()
                                ->markAsRequired(false)
                                ->default($record ? $record->abdd_id : null)
                                ->options(function () {
                                    return Abdd::whereNull('deleted_at')
                                        ->whereNot('name', 'Not Applicable')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray() ?: ['no_abdd' => 'No ABDD sectors available'];
                                })
                                ->disabled()
                                ->dehydrated(),

                            Select::make('delivery_mode_id')
                                ->label('Delivery Mode')
                                ->required()
                                ->markAsRequired(false)
                                ->default($record ? $record->delivery_mode_id : null)
                                ->options(function () {
                                    return DeliveryMode::whereNull('deleted_at')
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray() ?: ['no_delivery_mode' => 'No delivery modes available'];
                                })
                                ->disabled()
                                ->dehydrated()
                                ->validationAttribute('delivery mode'),

                            Select::make('learning_mode_id')
                                ->label('Learning Mode')
                                ->default($record ? $record->learning_mode_id : null)
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
                                ->disabled()
                                ->dehydrated()
                                ->validationAttribute('learning mode'),

                            TextInput::make('number_of_slots')
                                ->label('Slots')
                                ->required()
                                ->markAsRequired(false)
                                ->default($record ? $record->number_of_slots : null)
                                ->numeric()
                                ->rules(['min: 10', 'max: 25'])
                                ->validationAttribute('slots')
                                ->validationMessages([
                                    'min' => 'The number of slots must be at least 10.',
                                    'max' => 'The number of slots must not exceed 25.'
                                ])
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('per_capita_cost')
                                ->label('Per Capita Cost')
                                ->required()
                                ->markAsRequired(false)
                                ->autocomplete(false)
                                ->default($record ? $record->total_amount / $record->number_of_slots : null)
                                ->prefix('₱')
                                ->numeric()
                                ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                                ->disabled()
                                ->dehydrated()
                                ->hidden(function ($get) {
                                    $id = $get('qualification_title_id');
                                    $qualificationTitle = QualificationTitle::find($id)?->soc;

                                    return $qualificationTitle;
                                })
                                ->validationMessages([
                                    'max' => 'The PCC cannot exceed ₱999,999,999,999.99.'
                                ])
                                ->validationAttribute('PCC'),

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
                    Section::make('Target Details')
                        ->schema($createCommonFields($record, false))
                        ->columns(2),
                ];
            } else {
                $urlParams = request()->get('record');
                $record = Target::find($urlParams);

                return [
                    Section::make('Target Information')
                        ->schema($createCommonFields($record, true))
                        ->columns(2),
                ];
            }
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('allocation.particular.subParticular.fundSource.name')
                    ->label('Fund Source')
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
                    ->searchable()
                    ->sortable()
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

                TextColumn::make('appropriation_type')
                    ->label('Appropriation Type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.year')
                    ->label('Allocation Year')
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
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter compliant target')),
            ])
            ->actions([
                ActionGroup::make([
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

                    DeleteAction::make()
                        ->action(function ($record) {
                            try {
                                $allocation = $record->allocation;
                                $totalAmount = $record->total_amount;

                                $qualificationTitleId = $record->qualification_title_id;
                                $quali = QualificationTitle::find($qualificationTitleId);
                                $trainingProgramId = $quali?->training_program_id;

                                $provinceId = $record->tvi->district->province_id;
                                $districtId = $record->tvi->district_id;

                                $toolkit = $quali?->toolkits()->where('year', $allocation->year)->first();

                                $stepId = ScholarshipProgram::where('name', 'STEP')->value('id');
                                $compliantId = TargetStatus::where("desc", "Compliant")->value('id');

                                $slots = $record->number_of_slots;

                                if ($quali?->scholarship_program_id === $stepId && $record->target_status_id === $compliantId) {
                                    if ($toolkit) {
                                        $toolkit->increment('available_number_of_toolkits', $slots);
                                    }
                                }

                                $activeStatusId = Status::where('desc', 'Active')->value('id');

                                $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                    ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $activeStatusId) {
                                        $query->where('province_id', $provinceId)
                                            ->where('district_id', $districtId)
                                            ->where('year', $allocation->year)
                                            ->where('status_id', $activeStatusId);
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

                                if ($skillPrograms) {
                                    $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);
                                    if ($skillsPriority) {
                                        $skillsPriority->increment('available_slots', $slots);
                                    }
                                }

                                $allocation->increment('balance', $totalAmount);
                                $record->delete();

                                NotificationHandler::sendSuccessNotification('Deleted', 'Target has been deleted successfully.');
                            } catch (\Exception $e) {
                                NotificationHandler::handleValidationException('Something went wrong', $e->getMessage());
                            }
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete compliant target')),

                    RestoreAction::make()
                        ->action(function ($record) {
                            try {
                                $allocation = $record->allocation;
                                $totalAmount = $record->total_amount;

                                $qualificationTitleId = $record->qualification_title_id;
                                $quali = QualificationTitle::find($qualificationTitleId);
                                $trainingProgramId = $quali?->training_program_id;

                                $provinceId = $record->tvi->district->province_id;
                                $districtId = $record->tvi->district_id;

                                $toolkit = $quali?->toolkits()->where('year', $allocation->year)->first();

                                $stepId = ScholarshipProgram::where('name', 'STEP')->value('id');
                                $compliantId = TargetStatus::where("desc", "Compliant")->value('id');

                                $slots = $record->number_of_slots;
                                $totalCostOfToolkit = 0;

                                if ($quali?->scholarship_program_id === $stepId && $record->target_status_id === $compliantId) {
                                    if ($toolkit) {
                                        $toolkit->decrement('available_number_of_toolkits', $slots);
                                    }
                                }

                                $activeStatusId = Status::where('desc', 'Active')->value('id');

                                $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                    ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $activeStatusId) {
                                        $query->where('province_id', $provinceId)
                                            ->where('district_id', $districtId)
                                            ->where('year', $allocation->year)
                                            ->where('status_id', $activeStatusId);
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

                                if ($skillPrograms) {
                                    $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

                                    if ($skillsPriority && $skillsPriority->available_slots < $slots) {
                                        throw new \Exception("Insufficient Target Beneficiaries for the Skill Priority of {$quali?->trainingProgram?->title} under District {$record->tvi->district->name} in {$record->tvi->district->province->name}.");
                                    }

                                    $skillsPriority?->decrement('available_slots', $slots);
                                }

                                if ($allocation->balance < $totalAmount + $totalCostOfToolkit) {
                                    throw new \Exception("Insufficient Allocation Balance for {$allocation->legislator->name}.");
                                }

                                $allocation->decrement('balance', $totalAmount + $totalCostOfToolkit);

                                $record->restore();

                                NotificationHandler::sendSuccessNotification('Restored', 'Target has been restored successfully.');
                            } catch (\Exception $e) {
                                NotificationHandler::handleValidationException('Something went wrong', $e->getMessage());
                            }
                        }),

                    ForceDeleteAction::make()
                        ->action(function ($record) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Target has been permanently deleted.');
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            try {
                                foreach ($records as $record) {
                                    $allocation = $record->allocation;
                                    $totalAmount = $record->total_amount;

                                    $qualificationTitleId = $record->qualification_title_id;
                                    $quali = QualificationTitle::find($qualificationTitleId);
                                    $trainingProgramId = $quali?->training_program_id;

                                    $provinceId = $record->tvi->district->province_id;
                                    $districtId = $record->tvi->district_id;

                                    $toolkit = $quali?->toolkits()->where('year', $allocation->year)->first();

                                    $stepId = ScholarshipProgram::where('name', 'STEP')->value('id');
                                    $compliantId = TargetStatus::where("desc", "Compliant")->value('id');

                                    $slots = $record->number_of_slots;

                                    if ($quali?->scholarship_program_id === $stepId && $record->target_status_id === $compliantId) {
                                        if ($toolkit) {
                                            $toolkit->increment('available_number_of_toolkits', $slots);
                                        }
                                    }

                                    $activeStatusId = Status::where('desc', 'Active')->value('id');

                                    $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                        ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $activeStatusId) {
                                            $query->where('province_id', $provinceId)
                                                ->where('district_id', $districtId)
                                                ->where('year', $allocation->year)
                                                ->where('status_id', $activeStatusId);
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

                                    if ($skillPrograms) {
                                        $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);
                                        if ($skillsPriority) {
                                            $skillsPriority->increment('available_slots', $slots);
                                        }
                                    }

                                    $allocation->increment('balance', $totalAmount);
                                    $record->delete();
                                }

                                NotificationHandler::sendSuccessNotification('Deleted', 'Targets have been deleted successfully.');
                            } catch (\Exception $e) {
                                NotificationHandler::handleValidationException('Something went wrong', $e->getMessage());
                            }
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete compliant target')),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            try {
                                foreach ($records as $record) {
                                    $allocation = $record->allocation;
                                    $totalAmount = $record->total_amount;

                                    $qualificationTitleId = $record->qualification_title_id;
                                    $quali = QualificationTitle::find($qualificationTitleId);
                                    $trainingProgramId = $quali?->training_program_id;

                                    $provinceId = $record->tvi->district->province_id;
                                    $districtId = $record->tvi->district_id;

                                    $toolkit = $quali?->toolkits()->where('year', $allocation->year)->first();

                                    $stepId = ScholarshipProgram::where('name', 'STEP')->value('id');
                                    $compliantId = TargetStatus::where("desc", "Compliant")->value('id');

                                    $slots = $record->number_of_slots;
                                    $totalCostOfToolkit = 0;

                                    if ($quali?->scholarship_program_id === $stepId && $record->target_status_id === $compliantId) {
                                        if ($toolkit) {
                                            $toolkit->decrement('available_number_of_toolkits', $slots);
                                        }
                                    }

                                    $activeStatusId = Status::where('desc', 'Active')->value('id');

                                    $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                                        ->whereHas('skillPriority', function ($query) use ($provinceId, $districtId, $allocation, $activeStatusId) {
                                            $query->where('province_id', $provinceId)
                                                ->where('district_id', $districtId)
                                                ->where('year', $allocation->year)
                                                ->where('status_id', $activeStatusId);
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

                                    if ($skillPrograms) {
                                        $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

                                        if ($skillsPriority && $skillsPriority->available_slots < $slots) {
                                            throw new \Exception("Insufficient Target Beneficiaries for the Skill Priority of {$quali?->trainingProgram?->title} under District {$record->tvi->district->name} in {$record->tvi->district->province->name}.");
                                        }

                                        $skillsPriority?->decrement('available_slots', $slots);
                                    }

                                    if ($allocation->balance < $totalAmount + $totalCostOfToolkit) {
                                        throw new \Exception("Insufficient Allocation Balance for {$allocation->legislator->name}.");
                                    }

                                    $allocation->decrement('balance', $totalAmount + $totalCostOfToolkit);

                                    $record->restore();
                                }

                                NotificationHandler::sendSuccessNotification('Restored', 'Targets have been restored successfully.');
                            } catch (\Exception $e) {
                                NotificationHandler::handleValidationException('Something went wrong', $e->getMessage());
                            }
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('restore compliant target')),


                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected targets have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('force delete compliant target ')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomCompliantTarget::make()
                                ->withColumns([
                                    Column::make('allocation.particular.subParticular.fundSource.name')
                                        ->heading('Fund Source')
                                        ->getStateUsing(function ($record) {
                                            $particular = $record->allocation->particular;
                                            $subParticular = $particular->subParticular;
                                            $fundSource = $subParticular ? $subParticular->fundSource : null;

                                            return $fundSource ? $fundSource->name : '-';
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
                                ->withFilename(date('m-d-Y') . ' - Compliant Targets')
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

        $query = Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->whereIn('year', [$yearNow, $yearNow - 1]);

        if (!empty($attributorId)) {
            $query->where('attributor_id', $attributorId)
                ->where('attributor_particular_id', $attributorParticularId);
        }

        $allocations = $query->pluck('year', 'year')->toArray();

        return !empty($allocations) ? $allocations : ['no_allocation' => 'No appropriation year available'];
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
            return ['no_qualification_title' => 'No qualification titles available. No skills priorities with sufficient target beneficiaries in the selected province.'];
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
            'index' => Pages\ListCompliantTargets::route('/'),
            'create' => Pages\CreateCompliantTargets::route('/create'),
        ];
    }
}
