<?php

namespace App\Filament\Resources;

use App\Models\Tvi;
use App\Models\TviClass;
use App\Models\InstitutionClass;
use App\Models\District;
use App\Filament\Resources\TviResource\Pages;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Services\NotificationHandler;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TviResource extends Resource
{
    protected static ?string $model = Tvi::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = 'Institutions';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("school_id")
                    ->label('School ID')
                    ->placeholder(placeholder: 'Enter school ID')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('School ID'),
                
                TextInput::make("name")
                    ->label('Institution')
                    ->placeholder(placeholder: 'Enter institution name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Institution'),

                Select::make('tvi_class_id')
                    ->label("Institution Class (A)")
                    ->relationship('tviClass', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        $tviClasses = TviClass::all();
                    
                        $institutionTypes = [
                            'Private' => 1,
                            'Public'  => 2,
                        ];
                    
                        $getClassOptions = function ($typeId) use ($tviClasses) {
                            return $tviClasses
                                ->where('tvi_type_id', $typeId)
                                ->pluck('name', 'id')
                                ->toArray();
                        };
                    
                        $privateClasses = $getClassOptions($institutionTypes['Private']);
                        $publicClasses = $getClassOptions($institutionTypes['Public']);
                    
                        return [
                            'Private' => $privateClasses ?: ['no_private_class' => 'No Private Institution Class Available'],
                            'Public'  => $publicClasses  ?: ['no_public_class' => 'No Public Institution Class Available'],
                        ];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_private_class' || $value === 'no_public_class'),
                
                Select::make('institution_class_id')
                    ->label("Institution Class (B)")
                    ->relationship('InstitutionClass', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return InstitutionClass::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_institution_class' => 'No Institution Class (B) Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_institution_class'),
                
                Select::make('district_id')
                    ->label('District')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return District::whereNot('name', 'Not Applicable')
                            ->get()
                            ->mapWithKeys(function (District $district) {
                                $label = $district->name . ' - ' .
                                    $district->municipality->name . ', ' .
                                    $district->municipality->province->name;

                                return [$district->id => $label];
                            })
                            ->toArray() ?: ['no_district' => 'No District Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_district'),
                
                TextInput::make("address")
                    ->label("Full Address")
                    ->placeholder(placeholder: 'Enter institution address')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no institutions available')
            ->columns([
                TextColumn::make("school_id")
                    ->label("School ID")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("name")
                    ->label("Institution")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make("tviClass.name")
                    ->label('Institution Class(A)')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("InstitutionClass.name")
                    ->label("Institution Class(B)")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('district.name')
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $district = $record->district;

                        if (!$district) {
                            return 'No District Information';
                        }

                        $municipality = $district->municipality;
                        $province = $district->municipality->province;

                        $districtName = $district->name;
                        $municipalityName = $municipality ? $municipality->name : '-';
                        $provinceName = $province ? $province->name : '-';

                        return "{$districtName} - {$municipalityName}, {$provinceName}";
                    }),

                TextColumn::make("address")
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),

                SelectFilter::make('tvi_class_id')
                    ->label("Institution Class (A)")
                    ->relationship('tviClass', 'name'),
                    
                SelectFilter::make('institution_class_id')
                    ->label("Institution Class (B)")
                    ->relationship('InstitutionClass', 'name'),

                Filter::make('institution')
                    ->form([
                        Select::make('province_id')
                            ->label('Province')
                            ->placeholder('All')
                            ->options(Province::whereNot('name', 'Not Applicable')->pluck('name', 'id'))
                            ->afterStateUpdated(function (callable $set, $state) {
                                $set('municipality_id', null);
                                $set('district_id', null);
                            })
                            ->reactive(),
                
                        Select::make('municipality_id')
                            ->label('Municipality')
                            ->placeholder('All')
                            ->options(function ($get) {
                                $provinceId = $get('province_id');
                                
                                return Municipality::where('province_id', $provinceId)
                                    ->pluck('name', 'id');
                            })
                            ->afterStateUpdated(function (callable $set) {
                                $set('district_id', null);
                            })
                            ->reactive(),
                    
                        Select::make('district_id')
                            ->label('District')
                            ->placeholder('All')
                            ->options(function ($get) {
                                $municipalityId = $get('municipality_id');
                                
                                return District::where('municipality_id', $municipalityId)
                                    ->pluck('name', 'id');
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['province_id'] ?? null,
                                fn (Builder $query, $provinceId) => $query->whereHas('district', function (Builder $query) use ($provinceId) {
                                    $query->whereHas('municipality', function (Builder $query) use ($provinceId) {
                                        $query->where('province_id', $provinceId);
                                    });
                                })
                            )
                            ->when(
                                $data['municipality_id'] ?? null,
                                fn (Builder $query, $municipalityId) => $query->whereHas('district', function (Builder $query) use ($municipalityId) {
                                    $query->where('municipality_id', $municipalityId);
                                })
                            )
                            ->when(
                                $data['district_id'] ?? null,
                                fn (Builder $query, $districtId) => $query->where('district_id', $districtId)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                
                        if (!empty($data['province_id'])) {
                            $indicators[] = 'Province: ' . Province::find($data['province_id'])->name;
                        }
                
                        if (!empty($data['municipality_id'])) {
                            $indicators[] = 'Municipality: ' . Municipality::find($data['municipality_id'])->name;
                        }
                
                        if (!empty($data['district_id'])) {
                            $indicators[] = 'District: ' . District::find($data['district_id'])->name;
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
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institutions have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institutions have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('school_id')
                                        ->heading('School ID'),
                                    Column::make('name')
                                        ->heading('Institution Name'),
                                    Column::make('tviClass.name')
                                        ->heading('Institution Class (A)'),
                                    Column::make('InstitutionClass.name')
                                        ->heading('Institution Class (B)'),
                                    Column::make('district.name')
                                        ->heading('District')
                                        ->getStateUsing(function ($record) {
                                            $district = $record->district;

                                            $municipality = $district->municipality;
                                            $province = $district->municipality->province;

                                            $districtName = $district->name;
                                            $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
                                            $provinceName = $province ? $province->name : 'Unknown Province';

                                            return "{$districtName} - {$municipalityName}, {$provinceName}";
                                        }),
                                    Column::make('address')
                                        ->heading('Address'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institutions')
                        ]),
                ])
            ]);
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