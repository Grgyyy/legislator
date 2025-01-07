<?php

namespace App\Filament\Resources;

use App\Models\Tvi;
use App\Models\Abdd;
use Filament\Tables;
use App\Models\Target;
use Filament\Forms\Form;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use Filament\Tables\Table;
use App\Models\DeliveryMode;
use App\Models\TargetStatus;
use Filament\Actions\Action;
use App\Models\SubParticular;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\CompliantTargetsResource\Pages;

class CompliantTargetsResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Compliant Targets";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        $urlParams = request()->get('record');
        $record = Target::find($urlParams);

        return $form->schema([
            TextInput::make('abscap_id')
                ->label('Absorbative Capacity ID')
                ->placeholder('Enter an Absorbative capacity ID')
                ->default($record ? $record->abscap_id : null)
                ->disabled()
                ->dehydrated()
                ->numeric(),

            Select::make('sender_legislator_id')
                ->label('Attribution Sender')
                ->searchable()
                ->default($record->attributionAllocation->legislator_id ?? null) // Simplified with null coalescing
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
                ->default($record->attributionAllocation->particular_id ?? null)
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
                    $legislatorId = $get('legislator_id');
                    $particularId = $get('particular_id');
                    return $legislatorId ? self::getScholarshipProgramsOptions($legislatorId, $particularId) : ['' => 'No Scholarship Program Available.'];
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

            Select::make('legislator_id')
                ->label('Legislator Name')
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

            Select::make('particular_id')
                ->label('Particular')
                ->required()
                ->searchable()
                ->default($record ? $record->allocation->particular_id : null)
                ->options(function ($get) {
                    $legislatorId = $get('legislator_id');
                    return $legislatorId ? self::getParticularOptions($legislatorId) : ['' => 'No Particular Available.'];
                })
                ->reactive()
                ->disabled()
                ->dehydrated()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('scholarship_program_id', null);
                    $set('qualification_title_id', null);
                }),

            Select::make('tvi_id')
                ->label('Institution')
                ->required()
                ->searchable()
                ->preload()
                ->default($record ? $record->tvi_id : null)
                ->disabled()
                ->dehydrated()
                ->options(function () {
                    return TVI::whereNot('name', 'Not Applicable')
                        ->pluck('name', 'id')
                        ->mapWithKeys(function ($name, $id) {
                            $formattedName = preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($name));

                            return [$id => $formattedName];
                        })
                        ->toArray() ?: ['no_tvi' => 'No institution available'];
                }),

            Select::make('qualification_title_id')
                ->label('Qualification Title')
                ->required()
                ->searchable()
                ->disabled()
                ->dehydrated()
                ->default($record ? $record->qualification_title_id : null)
                ->options(function ($get) {
                    $scholarshipProgramId = $get('scholarship_program_id');
                    $tvi = $get('tvi_id');
                    $year = $get('allocation_year');
                    return $scholarshipProgramId ? self::getQualificationTitles($scholarshipProgramId, $tvi, $year) : ['' => 'No Qualification Title Available.'];
                }),

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
                ->disabled()
                ->dehydrated(),

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
                ->default($record ? $record->learning_mode_id : null)
                ->disableOptionWhen(fn($value) => $value === 'no_learning_modes')
                ->disabled()
                ->dehydrated(),

            Select::make('abdd_id')
                ->label('ABDD Sector')
                ->required()
                ->searchable()
                ->preload()
                ->disabled()
                ->dehydrated()
                ->default($record ? $record->abdd_id : null)
                ->options(function () {
                    $abdds = Abdd::all();
                    return $abdds->isNotEmpty()
                        ? $abdds->pluck('name', 'id')->toArray()
                        : ['no_abddd' => 'No ABDD Sector available.'];
                }),

            TextInput::make('number_of_slots')
                ->label('Number of Slots')
                ->default($record ? $record->number_of_slots : null)
                ->disabled()
                ->dehydrated()
                ->required()
                ->numeric(),

            TextInput::make('target_id')
                ->label('')
                ->default($record ? $record->id : 'id')
                ->extraAttributes(['class' => 'hidden'])
                ->required()
                ->disabled()
                ->dehydrated()
                ->numeric(),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('allocation.particular.subParticular.fundSource.name')
                    ->label('Allocation Type'),
                TextColumn::make('attributionAllocation.legislator.name')
                    ->label('Legislator I'),
                TextColumn::make('allocation.legislator.name')
                    ->label('Legislator II'),
                TextColumn::make('allocation.particular.subParticular.name')
                    ->label('Particular'),
                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Soft/Commitment'),
                TextColumn::make('appropriation_type')
                    ->label('Appropriation Type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.year')
                    ->label('Allocation Year')
                    ->searchable()
                    ->toggleable(),
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
                    ->formatStateUsing(fn($state) => preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($state))),
                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program'),
                TextColumn::make('qualification_title.trainingProgram.title')
                    ->label('Qualification Title')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return $state;
                        }

                        $state = ucwords($state);

                        if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                            $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                return 'NC ' . strtoupper($matches[1]);
                            }, $state);
                        }

                        return $state;
                    }),
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
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('allocation.particular.subParticular.fundSource.name')
                                        ->heading('Allocation Type'),

                                    Column::make('attributionAllocation.legislator.name')
                                        ->heading('Legislator I'),

                                    Column::make('allocation.legislator.name')
                                        ->heading('Legislator II'),

                                    Column::make('allocation.particular.subParticular.name')
                                        ->heading('Particular'),

                                    Column::make('allocation.soft_or_commitment')
                                        ->heading('Soft/Commitment'),

                                    Column::make('appropriation_type')
                                        ->heading('Appropriation Type'),

                                    Column::make('allocation.year')
                                        ->heading('Allocation Year'),

                                    Column::make('tvi.district.name')
                                        ->heading('District'),

                                    Column::make('municipality.name')
                                        ->heading('Municipality'),

                                    Column::make('municipality.province.name')
                                        ->heading('Province'),

                                    Column::make('municipality.province.region.name')
                                        ->heading('Region'),

                                    Column::make('tvi.name')
                                        ->heading('Institution')
                                        ->formatStateUsing(fn($state) => preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($state))),

                                    Column::make('allocation.scholarship_program.name')
                                        ->heading('Scholarship Program'),

                                    Column::make('qualification_title.trainingProgram.title')
                                        ->heading('Qualification Title')
                                        ->formatStateUsing(function ($state) {
                                            if (!$state) {
                                                return $state;
                                            }

                                            $state = ucwords($state);

                                            if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                                                $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                                    return 'NC ' . strtoupper($matches[1]);
                                                }, $state);
                                            }

                                            return $state;
                                        }),

                                    Column::make('qualification_title.trainingProgram.priority.name')
                                        ->heading('Priority Sector'),

                                    Column::make('qualification_title.trainingProgram.tvet.name')
                                        ->heading('TVET Sector'),

                                    Column::make('abdd.name')
                                        ->heading('ABDD Sector'),

                                    Column::make('number_of_slots')
                                        ->heading('No. of Slots'),

                                    Column::make('total_amount')
                                        ->heading('Total Amount')
                                        ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                                    Column::make('targetStatus.desc')
                                        ->heading('Status')

                                ])
                                ->withFilename(date('m-d-Y') . ' - Compliant Targets')
                        ]),
                ]),
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
        $routeParameter = request()->route('record');
        $compliantStatus = TargetStatus::where('desc', 'Compliant')->first();

        if ($compliantStatus) {
            $query->withoutGlobalScopes([SoftDeletingScope::class])
                ->where('target_status_id', '=', $compliantStatus->id); // Use '=' for comparison

            if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
                $query->where('region_id', (int) $routeParameter);
            }
        }

        return $query;
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
                    return [$particular->id => $particular->subParticular->name . " - " . $particular->district->name . ', ' . $particular->district->underMunicipality->name];
                }

            })
            ->toArray();

        return empty($particulars) ? ['' => 'No Particular Available'] : $particulars;
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

        $qualificationTitles = QualificationTitle::whereIn('training_program_id', $skillPriorities)
            ->whereIn('training_program_id', $institutionPrograms)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->where('status_id', 1)
            ->where('soc', 1)
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

                return [$qualification->id => ucwords($title)];
            })
            ->toArray();

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
}
