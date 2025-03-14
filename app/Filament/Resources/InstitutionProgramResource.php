<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomInstitutionQualificationTitleExport;
use App\Filament\Resources\InstitutionProgramResource\Pages;
use App\Models\District;
use App\Models\InstitutionProgram;
use App\Models\Province;
use App\Models\Region;
use App\Models\Status;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
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
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class InstitutionProgramResource extends Resource
{
    protected static ?string $model = InstitutionProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?string $navigationLabel = "Institution Qualification Titles";

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tvi_id')
                    ->label('Institution')
                    ->relationship('tvi', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->default(fn($get) => request()->get('tvi_id'))
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
                    ->disableOptionWhen(fn($value) => $value === 'no_tvi')
                    ->validationAttribute('institution'),

                Select::make('training_program_id')
                    ->label('Qualification Title')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function () {
                        return TrainingProgram::all()
                            ->sortBy('title')
                            ->pluck('title', 'id')
                            ->mapWithKeys(function ($title, $id) {
                                $program = TrainingProgram::find($id);

                                return [$id => "{$program->soc_code} - {$program->title}"];
                            })
                            ->toArray() ?: ['no_training_program' => 'No qualification titles available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_training_program')
                    ->validationAttribute('qualification title'),

                Select::make('status_id')
                    ->required()
                    ->markAsRequired(false)
                    ->hidden(fn(Page $livewire) => $livewire instanceof CreateRecord)
                    ->default(1)
                    ->native(false)
                    ->options(function () {
                        return Status::all()
                            ->pluck('desc', 'id')
                            ->toArray() ?: ['no_status' => 'No status available'];
                    })
                    ->validationAttribute('status'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('trainingProgram.title')
            ->emptyStateHeading('No institution qualification titles available')
            ->paginated([5, 10, 25, 50])
            ->columns([
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
                    })
                    ->limit(50)
                    ->tooltip(fn($state): ?string => strlen($state) > 50 ? $state : null),

                TextColumn::make('trainingProgram.soc_code')
                    ->label('SOC Code')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->trainingProgram->soc_code ?? '-'),

                TextColumn::make('trainingProgram.title')
                    ->label('Qualification Title')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(50)
                    ->tooltip(fn($state): ?string => strlen($state) > 50 ? $state : null),

                TextColumn::make('tvi.district.name')
                    ->label('District')
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('tvi.district', function ($q) use ($search) {
                            $q->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($search) . "%"])
                                ->orWhereRaw("LOWER(name) = ?", [strtolower($search)]);
                        })
                            ->orWhereHas('tvi.district.province', function ($q) use ($search) {
                                $q->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($search) . "%"])
                                    ->orWhereRaw("LOWER(name) = ?", [strtolower($search)]);
                            })
                            ->orWhereHas('tvi.district.underMunicipality', function ($q) use ($search) {
                                $q->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($search) . "%"])
                                    ->orWhereRaw("LOWER(name) = ?", [strtolower($search)]);
                            });
                    })
                    ->toggleable()
                    ->getStateUsing(fn($record) => self::getLocationNames($record)),

                TextColumn::make('tvi.address')
                    ->label('Address')
                    ->sortable()
                    ->toggleable()
                    ->limit(45)
                    ->tooltip(fn($state): ?string => strlen($state) > 45 ? $state : null),

                SelectColumn::make('status_id')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '2' => 'Inactive',
                    ])
                    ->disablePlaceholderSelection()
                    ->extraAttributes(['style' => 'width: 125px;'])
                    ->toggleable()
                    ->sortable()
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter institution qualification title')),

                Filter::make('filter')
                    ->form(function () {
                        return [
                            Fieldset::make('Address')
                                ->schema([
                                    Select::make('region_id')
                                        ->label('Region')
                                        ->placeholder('Select region')
                                        ->searchable()
                                        ->options(function () {
                                            return Region::whereNot('name', 'Not Applicable')
                                                ->pluck('name', 'id')
                                                ->toArray() ?: ['no_region' => 'No regions available'];
                                        })
                                        ->disableOptionWhen(fn($value) => $value === 'no_region')
                                        ->afterStateUpdated(function (callable $set, $state) {
                                            if (!$state) {
                                                $set('province_id', null);
                                                $set('tvi_id', null);
                                                $set('training_program_id', null);

                                                return;
                                            }
                                        })
                                        ->reactive(),

                                    Select::make('province_id')
                                        ->label('Province')
                                        ->placeholder('Select province')
                                        ->searchable()
                                        ->options(function ($get) {
                                            $regionId = $get('region_id');

                                            if ($regionId) {
                                                return Province::whereNot('name', 'Not Applicable')
                                                    ->where('region_id', $regionId)
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_provinces' => 'No provinces available'];
                                            }

                                            return ['no_provinces' => 'No provinces available. Select a region first.'];
                                        })
                                        ->disableOptionWhen(fn($value) => $value === 'no_provinces')
                                        ->afterStateUpdated(function (callable $set, $state) {
                                            if (!$state) {
                                                $set('tvi_id', null);
                                                $set('training_program_id', null);

                                                return;
                                            }
                                        })
                                        ->reactive(),
                                ])
                                ->columns(1),

                            Select::make('tvi_id')
                                ->label('Institution')
                                ->placeholder('Select institution')
                                ->searchable()
                                ->options(function ($get) {
                                    $regionId = $get('region_id');
                                    $provinceId = $get('province_id');

                                    if (!$regionId && !$provinceId) {
                                        return Tvi::whereNot('name', 'Not Applicable')
                                            ->get()
                                            ->mapWithKeys(function ($tvi) {
                                                $schoolId = $tvi->school_id;
                                                $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;
                                                return [$tvi->id => $formattedName];
                                            })
                                            ->toArray() ?: ['no_tvi' => 'No institutions available'];
                                    }

                                    if (!$provinceId) {
                                        return Tvi::whereNot('name', 'Not Applicable')
                                            ->whereHas('district.province.region', function ($query) use ($regionId) {
                                                $query->where('id', $regionId);
                                            })
                                            ->get()
                                            ->mapWithKeys(function ($tvi) {
                                                $schoolId = $tvi->school_id;
                                                $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;

                                                return [$tvi->id => $formattedName];
                                            })
                                            ->toArray() ?: ['no_tvi' => 'No institutions available'];
                                    }

                                    return Tvi::whereNot('name', 'Not Applicable')
                                        ->whereHas('district', function ($query) use ($provinceId) {
                                            $query->where('province_id', $provinceId);
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($tvi) {
                                            $schoolId = $tvi->school_id;
                                            $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;

                                            return [$tvi->id => $formattedName];
                                        })
                                        ->toArray() ?: ['no_tvi' => 'No institutions available'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_tvi')
                                ->reactive(),

                            Select::make('training_program_id')
                                ->label('Qualification Title')
                                ->placeholder('Select qualification title')
                                ->searchable()
                                ->options(function ($get) {
                                    $regionId = $get('region_id');
                                    $provinceId = $get('province_id');

                                    if (!$regionId && !$provinceId) {
                                        return Tvi::whereNot('name', 'Not Applicable')
                                            ->get()
                                            ->mapWithKeys(function ($tvi) {
                                                $schoolId = $tvi->school_id;
                                                $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;
                                                return [$tvi->id => $formattedName];
                                            })
                                            ->toArray() ?: ['no_tvi' => 'No institutions available'];
                                    }

                                    if (!$provinceId) {
                                        return Tvi::whereNot('name', 'Not Applicable')
                                            ->whereHas('district.province.region', function ($query) use ($regionId) {
                                                $query->where('id', $regionId);
                                            })
                                            ->get()
                                            ->mapWithKeys(function ($tvi) {
                                                $schoolId = $tvi->school_id;
                                                $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;

                                                return [$tvi->id => $formattedName];
                                            })
                                            ->toArray() ?: ['no_tvi' => 'No institutions available'];
                                    }

                                    $districtIds = District::where('province_id', $provinceId)
                                        ->pluck('id');

                                    $tviIds = Tvi::whereIn('district_id', $districtIds)
                                        ->pluck('id');

                                    return TrainingProgram::whereHas('tvis', function ($query) use ($tviIds) {
                                        $query->whereIn('tvi_id', $tviIds);
                                    })
                                        ->get()
                                        ->mapWithKeys(function ($program) {
                                            return [$program->id => "{$program->soc_code} - {$program->title}"];
                                        })
                                        ->toArray();
                                })
                                ->reactive()
                        ];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['region_id'] ?? null,
                                fn(Builder $query, $regionId) => $query->whereHas('tvi.district.province.region', function ($query) use ($regionId) {
                                    $query->where('id', $regionId);
                                })
                            )

                            ->when(
                                $data['province_id'] ?? null,
                                fn(Builder $query, $provinceId) => $query->whereHas('tvi.district.province', function ($query) use ($provinceId) {
                                    $query->where('id', $provinceId);
                                })
                            )

                            ->when(
                                $data['tvi_id'] ?? null,
                                fn(Builder $query, $tviId) => $query->where('tvi_id', $tviId)
                            )

                            ->when(
                                $data['training_program_id'] ?? null,
                                fn(Builder $query, $trainingProgramId) => $query->whereHas('trainingProgram', function ($query) use ($trainingProgramId) {
                                    $query->where('id', $trainingProgramId);
                                })
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['region_id'])) {
                            $indicators[] = 'Region: ' . Region::find($data['region_id'])->name;
                        }

                        if (!empty($data['province_id'])) {
                            $indicators[] = 'Province: ' . Province::find($data['province_id'])->name;
                        }

                        if (!empty($data['tvi_id'])) {
                            $tvi = Tvi::find($data['tvi_id']);
                            if ($tvi) {
                                $indicators[] = 'Institution: ' . $tvi->school_id . ' - ' . $tvi->name;
                            }
                        }

                        if (!empty($data['training_program_id'])) {
                            $trainingProgram = TrainingProgram::find($data['training_program_id']);
                            if ($trainingProgram) {
                                $indicators[] = 'Qualification Title: ' . $trainingProgram->soc_code . ' - ' . $trainingProgram->title;
                            }
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),

                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Institution has been deleted successfully.');
                        }),

                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Institution has been restored successfully.');
                        }),

                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Institution has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected institutions have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete institution qualification title')),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institutions have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore institution qualification title')),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institutions have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete institution qualification title')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomInstitutionQualificationTitleExport::make()
                                ->withColumns([
                                    Column::make('school_id')
                                        ->heading('School ID')
                                        ->getStateUsing(fn($record) => $record->school_id ?? '-'),

                                    Column::make('tvi.name')
                                        ->heading('Institution'),

                                    Column::make('trainingProgram.soc_code')
                                        ->heading('SOC Code')
                                        ->getStateUsing(fn($record) => $record->trainingProgram->soc_code ?? '-'),

                                    Column::make('trainingProgram.title')
                                        ->heading('Qualification Title'),

                                    Column::make('tvi.district.name')
                                        ->heading('District'),

                                    Column::make('tvi.municipality.name')
                                        ->heading('Municipality'),

                                    Column::make('tvi.district.province.name')
                                        ->heading('Province'),

                                    Column::make('tvi.district.province.region.name')
                                        ->heading('Region'),

                                    Column::make('tvi.address')
                                        ->heading('Address'),

                                    Column::make('status.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Qualification Titles')
                        ]),
                ])
                    ->label('Select Action'),
            ]);
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        $query->withoutGlobalScopes([SoftDeletingScope::class]);

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('tvi_id', (int) $routeParameter);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutionPrograms::route('/'),
            'create' => Pages\CreateInstitutionProgram::route('/create'),
            'edit' => Pages\EditInstitutionProgram::route('/{record}/edit'),
            'showPrograms' => Pages\ShowInstitutionProgram::route('/{record}/showPrograms')
        ];
    }
}
