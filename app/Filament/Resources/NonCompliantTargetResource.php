<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomNonCompliantTarget;
use App\Filament\Resources\NonCompliantTargetResource\Pages;
use App\Filament\Resources\NonCompliantTargetResource\RelationManagers;
use App\Models\Abdd;
use App\Models\Allocation;
use App\Models\DeliveryMode;
use App\Models\Legislator;
use App\Models\NonCompliantTarget;
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
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class NonCompliantTargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = "Non-Compliant Targets";

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema(function ($record) {
            $createCommonFields = function ($record, $isDisabled = true) {
                return [
                    Select::make('sender_legislator_id')
                        ->label('Attribution Sender')
                        ->searchable()
                        ->default($record->allocation->attributor_id ?? null)
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
                        ->reactive()
                        ->disabled()
                        ->dehydrated()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('sender_particular_id', null);
                        }),

                    Select::make('sender_particular_id')
                        ->label('Attributor Particular')
                        ->searchable()
                        ->default($record->allocation->attributor_particular_id ?? null)
                        ->options(function ($get) {
                            $legislatorId = $get('sender_legislator_id');

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
                        ->reactive()
                        ->disabled()
                        ->dehydrated()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('scholarship_program_id', null);
                            $set('qualification_title_id', null);
                        }),

                    Select::make('scholarship_program_id')
                        ->label('Scholarship Program')
                        ->required()
                        ->searchable()
                        ->default($record ? $record->allocation->scholarship_program_id : null)
                        ->options(function ($get) {
                            $legislatorId = $get('receiver_legislator_id');
                            $particularId = $get('receiver_particular_id');
                            return $legislatorId && $particularId ? self::getScholarshipProgramsOptions($legislatorId, $particularId) : ['' => 'No Scholarship Program Available.'];
                        })
                        ->reactive()
                        ->disabled()
                        ->dehydrated()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('allocation_year', null);
                            $set('qualification_title_id', null);
                        }),

                    Select::make('allocation_year')
                        ->label('Appropriation Year')
                        ->required()
                        ->searchable()
                        ->disabled()
                        ->dehydrated()
                        ->default($record ? $record->allocation->year : null)
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
                        ->default($record ? $record->appropriation_type : null)
                        ->disabled()
                        ->dehydrated()
                        ->options([
                            'Current' => 'Current',
                            'Continuing' => 'Continuing',
                        ]),

                    Select::make('receiver_legislator_id')
                        ->label('Attribution Receiver')
                        ->required()
                        ->searchable()
                        ->default($record ? $record->allocation->legislator_id : null)
                        ->options(function () {
                            $legislators = Legislator::where('status_id', 1)
                                ->whereNull('deleted_at')
                                ->has('allocation')
                                ->pluck('name', 'id')
                                ->toArray();

                            return empty($legislators) ? ['' => 'No Legislator Available.'] : $legislators;
                        })
                        ->reactive()
                        ->disabled()
                        ->dehydrated()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('particular_id', null);
                        }),

                    Select::make('receiver_particular_id')
                        ->label('Particular')
                        ->required()
                        ->markAsRequired(false)
                        ->default($record ? $record->allocation->particular_id : null)
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->options(function ($get, $set) {
                            $legislatorId = $get('receiver_legislator_id');

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

                                return $particularOptions ?: ['no_particular' => 'No particular available'];
                            }

                            return ['no_particular' => 'No particular available. Select a legislator first.'];
                        })
                        ->disableOptionWhen(fn($value) => $value === 'no_particular')
                        ->dehydrated()
                        ->disabled()
                        ->reactive()
                        ->live(),

                    Select::make('tvi_id')
                        ->label('Institution')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default($record ? $record->tvi_id : null)
                        ->disabled($isDisabled)
                        ->dehydrated()
                        ->options(function () {
                            return TVI::whereNot('name', 'Not Applicable')
                                ->pluck('name', 'id')
                                ->mapWithKeys(function ($name, $id) {
                                    $formattedName = preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($name));

                                    return [$id => $formattedName];
                                })
                                ->toArray() ?: ['no_tvi' => 'No institution available'];
                        })
                        ->afterStateUpdated(function (callable $set, $state) {
                            if (!$state) {
                                $set('qualification_title_id', null);
                            }

                            $set('qualification_title_id', null);

                        })
                        ->reactive()
                        ->live(),

                    Select::make('qualification_title_id')
                        ->label('Qualification Title')
                        ->required()
                        ->markAsRequired(false)
                        ->searchable()
                        ->default($record ? $record->qualification_title_id : null)
                        ->options(function ($get) {
                            $scholarshipProgramId = $get('scholarship_program_id');
                            $tviId = $get('tvi_id');
                            $year = $get('allocation_year');

                            return $scholarshipProgramId
                                ? self::getQualificationTitles($scholarshipProgramId, $tviId, $year)
                                : ['no_qualification_title' => 'No qualification title available. Select a scholarship program first.'];
                        })
                        ->disabled($isDisabled)
                        ->dehydrated(),

                    Select::make('delivery_mode_id')
                        ->label('Delivery Mode')
                        ->required()
                        ->markAsRequired(false)
                        ->searchable()
                        ->preload()
                        ->default($record ? $record->delivery_mode_id : null)
                        ->options(function () {
                            $deliveryModes = DeliveryMode::all();

                            return $deliveryModes->isNotEmpty()
                                ? $deliveryModes->pluck('name', 'id')->toArray()
                                : ['no_delivery_mode' => 'No delivery modes available.'];
                        })
                        ->disableOptionWhen(fn($value) => $value === 'no_delivery_mode')
                        ->disabled($isDisabled)
                        ->dehydrated(),

                    Select::make('learning_mode_id')
                        ->label('Learning Mode')
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
                        ->default($record ? $record->learning_mode_id : null)
                        ->disableOptionWhen(fn($value) => $value === 'no_learning_modes')
                        ->disabled($isDisabled)
                        ->dehydrated(),

                    Select::make('abdd_id')
                        ->label('ABDD Sector')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default($record ? $record->abdd_id : null)
                        // ->options(function ($get) {
                        //     $tviId = $get('tvi_id');
                        //     return $tviId ? self::getAbddSectors($tviId) : ['' => 'No ABDD Sector Available.'];
                        // })
                        ->options(function () {
                            return Abdd::whereNull('deleted_at')
                                ->pluck('name', 'id')
                                ->toArray() ?: ['no_abdd' => 'No ABDD Sectors available'];
                        })
                        ->disabled($isDisabled)
                        ->dehydrated(),

                    TextInput::make('number_of_slots')
                        ->label('Number of Slots')
                        ->default($record ? $record->number_of_slots : null)
                        ->required()
                        ->numeric()
                        ->markAsRequired(false)
                        ->disabled($isDisabled)
                        ->dehydrated(),

                    TextInput::make('target_id')
                        ->label('')
                        ->default($record ? $record->id : null)
                        ->extraAttributes(['class' => 'hidden'])
                        ->numeric(),
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
                    Section::make('Remarks')->schema([
                        Select::make('remarks_id')
                            ->label('Remarks')
                            ->options(TargetRemark::pluck('remarks', 'id')->toArray())
                            ->searchable()
                            ->required(),
                        Textarea::make('other_remarks')
                            ->label('If others, please specify:'),
                    ]),
                ];
            }
        });
    }


    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // TextColumn::make('abscap_id')
                //     ->sortable()
                //     ->searchable()
                //     ->toggleable(),

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
                    ->label('Source of Fund')
                    ->searchable()
                    ->toggleable(),


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

                TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('municipality.name')
                    ->label('Municipality')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.district.province.name')
                    ->label('Province')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi.district.province.region.name')
                    ->label('Region')
                    ->searchable()
                    ->toggleable(),


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

                TextColumn::make('nonCompliantRemark.target_remarks.remarks')
                    ->label('Remarks')
                    ->formatStateUsing(function ($record) {
                        if ($record->nonCompliantRemark) {
                            $targetRemarksId = $record->nonCompliantRemark->target_remarks_id;

                            $remark = TargetRemark::find($targetRemarksId);

                            return $remark->remarks ?? 'N/A';
                        }

                        return 'N/A';
                    }),

                TextColumn::make('nonCompliantRemark.others_remarks')
                    ->label('Other')
                    ->formatStateUsing(function ($record) {
                        if ($record->nonCompliantRemark) {
                            return $record->nonCompliantRemark->others_remarks ?? 'N/A';
                        }
                        return 'N/A';
                    }),

                TextColumn::make('targetStatus.desc')
                    ->label('Status')
                    ->searchable()
                    ->toggleable(),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id]),
            )
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditACtion::make()
                        ->hidden(fn($record) => $record->trashed())
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin', 'TESDO', 'SMD Head']) || Auth::user()->can('delete target ')),
                    Action::make('viewHistory')
                        ->label('View History')
                        ->url(fn($record) => route('filament.admin.resources.targets.showHistory', ['record' => $record->id]))
                        ->icon('heroicon-o-magnifying-glass'),
                    Action::make('viewComment')
                        ->label('View Comments')
                        ->url(fn($record) => route('filament.admin.resources.targets.showComments', ['record' => $record->id]))
                        ->icon('heroicon-o-chat-bubble-left-ellipsis'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete attribution project proposal target')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomNonCompliantTarget::make()
                                ->withColumns([
                                    // Column::make('abscap_id')
                                    //     ->heading('Absorptive Capacity'),
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


                                    Column::make('nonCompliantRemark.target_remarks.remarks')
                                        ->heading('Remarks')
                                        ->formatStateUsing(function ($record) {
                                            if ($record->nonCompliantRemark) {
                                                $targetRemarksId = $record->nonCompliantRemark->target_remarks_id;

                                                $remark = TargetRemark::find($targetRemarksId);

                                                return $remark->remarks ?? 'N/A';
                                            }

                                            return 'N/A';
                                        }),


                                    Column::make('nonCompliantRemark.others_remarks')
                                        ->heading('Other')
                                        ->formatStateUsing(function ($record) {
                                            if ($record->nonCompliantRemark->others_remarks) {
                                                return $record->nonCompliantRemark->others_remarks ?? 'N/A';
                                            }
                                            return 'N/A';
                                        }),

                                    Column::make('targetStatus.desc')
                                        ->heading('Status'),

                                ])
                                ->withFilename(date('m-d-Y') . ' - non_compliant_target_export')
                        ]),
                ])
                ->label('Select Action'),
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
            'index' => Pages\ListNonCompliantTargets::route('/'),
            'create' => Pages\CreateNonCompliantTarget::route('/create'),
            'edit' => Pages\EditNonCompliantTarget::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');
        $nonCompliantStatus = TargetStatus::where('desc', 'Non-Compliant')->first();

        if ($nonCompliantStatus) {
            $query->withoutGlobalScopes([SoftDeletingScope::class])
                ->where('target_status_id', '=', $nonCompliantStatus->id); // Use '=' for comparison

            if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
                $query->where('region_id', (int) $routeParameter);
            }
        }

        return $query;
    }

    private function getQualificationTitle($record)
    {
        $qualificationCode = $record->qualification_title_soc_code ?? '-';
        $qualificationName = $record->qualification_title_name ?? '-';

        return "{$qualificationCode} - {$qualificationName}";
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

    protected static function getAbddSectors($tviId)
    {
        $tvi = Tvi::with(['district.municipality.province'])->find($tviId);

        if (!$tvi || !$tvi->district || !$tvi->municipality || !$tvi->district->province) {
            return ['' => 'No ABDD Sectors Available.'];
        }

        $abddSectors = $tvi->district->province->abdds()
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
