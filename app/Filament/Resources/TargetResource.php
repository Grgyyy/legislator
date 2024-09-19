<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetResource\Pages;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\Target;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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

class TargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('targets')
                    ->schema([
                        Select::make('allocation_type')
                            ->label('Allocation')
                            ->required()
                            ->options([
                                'RO' => 'RO',
                                'CO' => 'CO',
                            ]),

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
                            ->relationship('abdd', 'name'),

                        Select::make('tvi_id')
                            ->label('Institution')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('tvi', 'name'),

                        TextInput::make('number_of_slots')
                            ->label('Number of Slots')
                            ->required()
                            ->numeric(),
                    ])
                    ->columns(columns: 4)
                    ->columnSpanFull()
                    ->addActionLabel('+'),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No targets yet')
            ->columns([
                TextColumn::make('allocation_type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.legislator.particular.name')
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
                        $district = $particular->district;
                        $municipality = $district ? $district->municipality : null;

                        $districtName = $district ? $district->name : 'Unknown District';
                        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';

                        return $districtName === 'Not Applicable'
                            ? $particular->name
                            : "{$particular->name} - {$districtName}, {$municipalityName}";
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
                TextColumn::make('abdd.name')
                    ->searchable()
                    ->toggleable()
                    ->label('ABDD Sector'),
                TextColumn::make('number_of_slots')
                    ->searchable()
                    ->toggleable()
                    ->label('No. of Slots'),
                TextColumn::make('qualification_title.pcc')
                    ->searchable()
                    ->toggleable()
                    ->label('Per Capita Cost')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
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
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
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
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTargets::route('/'),
            'create' => Pages\CreateTarget::route('/create'),
            'edit' => Pages\EditTarget::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function getParticularOptions($legislatorId) {
        $particulars = Particular::whereHas('allocation', function($query) use ($legislatorId) {
            $query->where('legislator_id', $legislatorId);
        })->pluck('name', 'id')->toArray();

        return empty($particulars) ? ['' => 'No Particular Available'] : $particulars;
    }

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId) {
        $scholarshipPrograms = ScholarshipProgram::whereHas('allocation', function($query) use ($legislatorId, $particularId) {
            $query->where('legislator_id', $legislatorId)
                ->where('particular_id', $particularId);
        })->pluck('name', 'id')->toArray();

        return empty($scholarshipPrograms) ? ['' => 'No Scholarship Program Available'] : $scholarshipPrograms;
    }

    protected static function getAllocationYear($legislatorId, $particularId, $scholarshipProgramId) {
        $yearNow = date('Y');
        $allocations = Allocation::where('legislator_id', $legislatorId)
                        ->where('particular_id', $particularId)
                        ->where('scholarship_program_id', $scholarshipProgramId)
                        ->whereIn('year', [$yearNow, $yearNow - 1])
                        ->pluck('year', 'id')
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
}
