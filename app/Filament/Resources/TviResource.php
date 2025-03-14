<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomInstitutionExport;
use App\Filament\Resources\TviResource\Pages;
use App\Models\District;
use App\Models\InstitutionClass;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Tvi;
use App\Models\TviClass;
use App\Models\TviType;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class TviResource extends Resource
{
    protected static ?string $model = Tvi::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = 'Institutions';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'institutions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("school_id")
                    ->label('School ID')
                    ->placeholder('Enter school ID')
                    ->autocomplete(false)
                    ->formatStateUsing(fn($state, $record) => $record && empty($state) ? '-' : $state)
                    ->maxLength(8)
                    ->validationAttribute('school ID'),

                TextInput::make("name")
                    ->label('Institution')
                    ->placeholder('Enter institution name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('institution'),

                Select::make('tvi_type_id')
                    ->label("Institution Type")
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function () {
                        return TviType::whereNull('deleted_at')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_institution_type' => 'No institution types available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_institution_type')
                    ->validationAttribute('institution type'),

                Select::make('tvi_class_id')
                    ->label("Institution Class (A)")
                    ->relationship('tviClass', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function ($get) {
                        $type = $get('tvi_type_id');

                        $publicTypes = ['Farm School', 'GOCC/GFI', 'HEI', 'LGU', 'LUC', 'NGA', 'SUC', 'TTI', 'TVI'];
                        $privateTypes = ['Enterprise', 'Farm School', 'HEI', 'LUC', 'NGO', 'TVI'];

                        $tviClasses = TviClass::all();

                        $publicId = TviType::where('name', 'Public')
                            ->value('id');
                        $privateId = TviType::where('name', 'Private')
                            ->value('id');

                        if ($type == $publicId) {
                            return $tviClasses
                                ->whereIn('name', $publicTypes)
                                ->sortBy('name')
                                ->pluck('name', 'id')
                                ->toArray() ?: ['no_public_class' => 'No public institution class available'];
                        }

                        if ($type == $privateId) {
                            return $tviClasses
                                ->whereIn('name', $privateTypes)
                                ->sortBy('name')
                                ->pluck('name', 'id')
                                ->toArray() ?: ['no_private_class' => 'No private institution class available'];
                        }

                        return ['no_insti_class' => 'No institution class available. Select an institution type first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_private_class' || $value === 'no_public_class' || $value === 'no_insti_class')
                    ->validationAttribute('institution class (A)'),

                Select::make('institution_class_id')
                    ->label("Institution Class (B)")
                    ->relationship('institutionClass', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function () {
                        return InstitutionClass::whereNull('deleted_at')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_institution_class' => 'No institution class (B) available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_institution_class')
                    ->validationAttribute('institution class (B)'),

                Select::make('district_id')
                    ->label('District')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function () {
                        return District::whereNot('name', 'Not Applicable')
                            ->get()
                            ->mapWithKeys(function (District $district) {
                                if ($district->underMunicipality) {
                                    $label = $district->name . ', ' . $district->underMunicipality->name . ', ' . $district->province->name;
                                } else {
                                    $label = $district->name . ', ' . $district->province->name;
                                }
                                return [$district->id => $label];
                            })
                            ->toArray() ?: ['no_district' => 'No districts available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_district')
                    ->afterStateUpdated(function ($state, $get, $set) {
                        if (!$state) {
                            $set('municipality_id', null);
                        }

                        $municipalities = self::getMunicipalitiesOptions($state);

                        $set('municipalityOptions', $municipalities);

                        if (count($municipalities) === 1) {
                            $set('municipality_id', key($municipalities));
                        } else {
                            $set('municipality_id', null);
                        }
                    })
                    ->reactive()
                    ->live()
                    ->validationAttribute('district'),

                Select::make('municipality_id')
                    ->label('Municipality')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function ($get) {
                        $districtId = $get('district_id');

                        if ($districtId) {
                            return Municipality::whereHas('district', function ($query) use ($districtId) {
                                $query->where('district_id', $districtId);
                            })
                                ->get()
                                ->mapWithKeys(function (Municipality $municipality) {
                                    $label = $municipality->name;
                                    return [$municipality->id => $label];
                                })
                                ->toArray() ?: ['no_municipality' => 'No municipalities available'];
                        }

                        return ['no_municipality' => 'No municipalities available. Select a district first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_municipality')
                    ->reactive()
                    ->live()
                    ->validationAttribute('municipality'),

                TextInput::make("address")
                    ->label("Full Address")
                    ->placeholder('Enter institution address')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('address'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->emptyStateHeading('No institutions available')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make("school_id")
                    ->label("School ID")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $schoolId = $record->school_id ?? '';

                        if (empty($schoolId)) {
                            return '-';
                        }

                        $cleaned = preg_replace('/\D/', '', $schoolId);

                        $cleaned = str_pad($cleaned, 8, '0', STR_PAD_LEFT);

                        return substr($cleaned, 0, 4) . '-' . substr($cleaned, 4, 4);
                    }),

                TextColumn::make("name")
                    ->label("Institution")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->limit(45)
                    ->tooltip(fn($state): ?string => strlen($state) > 45 ? $state : null),

                TextColumn::make("tviType.name")
                    ->label('Institution Type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("tviClass.name")
                    ->label('Institution Class(A)')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make("institutionClass.name")
                    ->label("Institution Class(B)")
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('district.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $district = $record->district;
                        $province = $district->province;

                        $municipalityName = $record->municipality ? $record->municipality->name : '';
                        $districtName = $district ? $district->name : '';
                        $provinceName = $province ? $province->name : '';

                        if ($municipalityName) {
                            return "{$districtName}, {$municipalityName}, {$provinceName}";
                        } else {
                            return "{$districtName}, {$provinceName}";
                        }
                    }),

                TextColumn::make("address")
                    ->toggleable()
                    ->limit(45)
                    ->tooltip(fn($state): ?string => strlen($state) > 45 ? $state : null),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.institution-recognitions.showRecognition', ['record' => $record->id]),
            )
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter institutions')),

                Filter::make('institution')
                    ->form(function () {
                        return [
                            Fieldset::make('Institution Classes')
                                ->schema([
                                    Select::make('tvi_class_id')
                                        ->label("Class (A)")
                                        ->preload()
                                        ->searchable()
                                        ->relationship('tviClass', 'name')
                                        ->reactive()
                                        ->live(),

                                    Select::make('institution_class_id')
                                        ->label("Class (B)")
                                        ->preload()
                                        ->searchable()
                                        ->relationship('InstitutionClass', 'name')
                                        ->reactive()
                                        ->live(),
                                ])
                                ->columns(1),

                            Fieldset::make('Location')
                                ->schema([
                                    Select::make('province_id')
                                        ->label('Province')
                                        ->preload()
                                        ->searchable()
                                        ->options(function () {
                                            return Province::whereNot('name', 'Not Applicable')
                                                ->pluck('name', 'id')
                                                ->toArray() ?: ['no_province' => 'No provinces available'];
                                        })
                                        ->afterStateUpdated(function (callable $set, $state) {
                                            $set('municipality_id', null);
                                            $set('district_id', null);
                                        })
                                        ->reactive()
                                        ->live(),

                                    Select::make('municipality_id')
                                        ->label('Municipality')
                                        ->preload()
                                        ->searchable()
                                        ->options(function ($get) {
                                            $provinceId = $get('province_id');

                                            if ($provinceId) {
                                                return Municipality::where('province_id', $provinceId)
                                                    ->pluck('name', 'id')
                                                    ->toArray() ?: ['no_municipality' => 'No municipalities available.'];
                                            }

                                            return ['no_municipality' => 'No municipalities available. Select a province first.'];
                                        })
                                        ->afterStateUpdated(function (callable $set) {
                                            $set('district_id', null);
                                        })
                                        ->reactive()
                                        ->live(),
                                ])
                                ->columns(1),
                        ];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tvi_class_id'] ?? null,
                                fn(Builder $query, $tviClassId) => $query->where('tvi_class_id', $tviClassId)
                            )

                            ->when(
                                $data['institution_class_id'] ?? null,
                                fn(Builder $query, $institutionClassId) => $query->where('institution_class_id', $institutionClassId)
                            )

                            ->when(
                                $data['province_id'] ?? null,
                                fn(Builder $query, $provinceId) => $query->whereHas('district', function (Builder $query) use ($provinceId) {
                                    $query->whereHas('municipality', function (Builder $query) use ($provinceId) {
                                        $query->where('province_id', $provinceId);
                                    });
                                })
                            )

                            ->when(
                                $data['municipality_id'] ?? null,
                                fn(Builder $query, $municipalityId) => $query->whereHas('district', function (Builder $query) use ($municipalityId) {
                                    $query->where('municipality_id', $municipalityId);
                                })
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['tvi_class_id'])) {
                            $indicators[] = 'Institution Class (A): ' . Optional(TviClass::find($data['tvi_class_id']))->name;
                        }

                        if (!empty($data['institution_class_id'])) {
                            $indicators[] = 'Institution Class (B): ' . Optional(InstitutionClass::find($data['institution_class_id']))->name;
                        }

                        if (!empty($data['province_id'])) {
                            $indicators[] = 'Province: ' . Province::find($data['province_id'])->name;
                        }

                        if (!empty($data['municipality_id'])) {
                            $indicators[] = 'Municipality: ' . Municipality::find($data['municipality_id'])->name;
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),

                    Action::make('showPrograms')
                        ->label('View Qualification Titles')
                        ->icon('heroicon-o-book-open')
                        ->url(fn($record) => route('filament.admin.resources.institution-programs.showPrograms', ['record' => $record->id])),

                    Action::make('showRecognitions')
                        ->label('View Recognition')
                        ->icon('heroicon-o-star')
                        ->url(fn($record) => route('filament.admin.resources.institution-recognitions.showRecognition', ['record' => $record->id])),

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
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete tvis')),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institutions have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore tvis')),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institutions have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete tvis')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomInstitutionExport::make()
                                ->withColumns([
                                    Column::make('school_id')
                                        ->heading('School ID')
                                        ->getStateUsing(fn($record) => $record->school_id ?? '-'),

                                    Column::make('name')
                                        ->heading('Institution'),

                                    Column::make('tviType.name')
                                        ->heading('Institution Type'),

                                    Column::make('tviClass.name')
                                        ->heading('Institution Class (A)'),

                                    Column::make('InstitutionClass.name')
                                        ->heading('Institution Class (B)'),

                                    Column::make('district.name')
                                        ->heading('District'),

                                    Column::make('municipality.name')
                                        ->heading('Municipality'),

                                    Column::make('municipality.province.name')
                                        ->heading('Province'),

                                    Column::make('address')
                                        ->heading('Address'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institutions')
                        ]),
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getMunicipalitiesOptions($districtId): array
    {
        return Municipality::whereHas('district', function ($query) use ($districtId) {
            $query->where('district_id', $districtId);
        })
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTvis::route('/'),
            'create' => Pages\CreateTvi::route('/create'),
            'edit' => Pages\EditTvi::route('/{record}/edit'),
        ];
    }
}
