<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomInstitutionQualificationTitleExport;
use App\Filament\Resources\InstitutionProgramResource\Pages;
use App\Filament\Resources\InstitutionProgramResource\RelationManagers;
use App\Models\District;
use App\Models\InstitutionProgram;
use App\Models\Province;
use App\Models\Region;
use App\Models\Status;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->default(fn($get) => request()->get('tvi_id'))
                    ->options(function () {
                        return Tvi::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->mapWithKeys(function ($name, $id) {
                                return [$id => $name];
                            })
                            ->toArray() ?: ['no_tvi' => 'No institution available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_tvi'),

                Select::make('training_program_id')
                    ->label('Qualification TItle')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return TrainingProgram::all()
                            ->pluck('title', 'id')
                            ->mapWithKeys(function ($title, $id) {
                                // Assuming `soc_code` is a column in the TrainingProgram model
                                $program = TrainingProgram::find($id);

                                return [$id => "{$program->soc_code} - {$program->title}"];
                            })
                            ->toArray() ?: ['no_training_program' => 'No Training Program Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_training_program')
                    ->live(),

                Select::make('status_id')
                    ->relationship('status', 'desc')
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
            ->columns([
                TextColumn::make('trainingProgram.soc_code')
                    ->label('SOC Code')
                    ->searchable(),
                TextColumn::make('trainingProgram.title')
                    ->label('Qualification Title')
                    ->searchable(),
                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->searchable(),
                TextColumn::make('tvi.district.name')
                    ->label('District')
                    ->searchable(),
                TextColumn::make('tvi.municipality.name')
                    ->label('Municipality')
                    ->searchable(),
                TextColumn::make('tvi.district.province.name')
                    ->label('Province')
                    ->searchable(),
                TextColumn::make('tvi.district.province.region.name')
                    ->label('Region')
                    ->searchable(),
                TextColumn::make('tvi.address')
                    ->label('Address')
                    ->searchable(),
                SelectColumn::make('status_id')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '2' => 'Inactive',
                    ])
                    ->disablePlaceholderSelection()
                    ->extraAttributes(['style' => 'width: 125px;'])

                // ->formatStateUsing(function ($state) {
                //     if (!$state) {
                //         return $state;
                //     }

                //     $state = ucwords($state);

                //     if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                //         $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                //             return 'NC ' . strtoupper($matches[1]);
                //         }, $state);
                //     }

                //     return $state;
                // })
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
                                        ->placeholder('All')
                                        ->options(
                                            Region::whereNot('name', 'Not Applicable')
                                                ->pluck('name', 'id')
                                        )
                                        ->afterStateUpdated(function (callable $set, $state) {
                                            $set('province_id', null);
                                        })
                                        ->reactive(),

                                    Select::make('province_id')
                                        ->label('Province')
                                        ->placeholder('All')
                                        ->options(function ($get) {
                                            $regionId = $get('region_id');

                                            return Province::whereNot('name', 'Not Applicable')
                                                ->where('region_id', $regionId)
                                                ->pluck('name', 'id');
                                        })
                                        ->reactive(),
                                ]),

                            Select::make('tvi_id')
                                ->label('Institution')
                                ->placeholder('Select institution')
                                ->searchable()
                                ->options(function ($get) {
                                    $provinceId = $get('province_id');

                                    $districtIds = District::where('province_id', $provinceId)->pluck('id');

                                    return Tvi::whereIn('district_id', $districtIds)
                                        ->whereNot('name', 'Not Applicable')
                                        ->get()
                                        ->mapWithKeys(fn($tvi) => [$tvi->id => "{$tvi->school_id} - {$tvi->name}"]);
                                })
                                ->reactive(),

                            Select::make('training_program_id')
                                ->label('Qualification Title')
                                ->placeholder('All')
                                ->searchable()
                                ->options(function ($get) {
                                    $provinceId = $get('province_id');
                                    $tviId = $get('tvi_id');

                                    if (!$provinceId) {
                                        return [];
                                    }

                                    $districtIds = District::where('province_id', $provinceId)
                                        ->pluck('id');

                                    $tviIds = Tvi::whereIn('district_id', $districtIds)
                                        ->pluck('id');

                                    if ($tviId) {
                                        $tviIds = [$tviId];
                                    }

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
                    EditAction::make()->hidden(fn($record) => $record->trashed()),
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
                                    Column::make('trainingProgram.soc_code')
                                        ->heading('SOC Code'),

                                    Column::make('trainingProgram.title')
                                        ->heading('Qualification Title'),

                                    Column::make('tvi.name')
                                        ->heading('Institution'),

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
                                        ->heading('Status')
                                        ->getStateUsing(fn($record) => $record->status?->desc ?? '-'),



                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Qualification Title Export')
                        ]),
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relationships here
        ];
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
