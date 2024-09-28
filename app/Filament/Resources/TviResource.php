<?php

namespace App\Filament\Resources;

use App\Models\Tvi;
use App\Models\District;
use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use App\Filament\Resources\TviResource\Pages;
use App\Models\InstitutionClass;
use App\Models\TviClass;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

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
                    ->required()
                    ->autocomplete(false)
                    ->markAsRequired(false),
                TextInput::make("name")
                    ->label('Institution')
                    ->required()
                    ->autocomplete(false)
                    ->markAsRequired(false),
                Select::make('tvi_class_id')
                    ->label("Institution Class (A)")
                    ->relationship('tviClass', 'name')
                    ->options(function () {
                        $tviClasses = TviClass::all();

                        $privateClasses = $tviClasses->whereIn('name', ['NGA', 'LGU', 'LUC', 'SUC', 'TTI'])->pluck('name', 'id')->toArray();
                        $publicClasses = $tviClasses->whereIn('name', ['HEI', 'TVI', 'NGO'])->pluck('name', 'id')->toArray();

                        return [
                            'Private' => !empty($privateClasses) ? $privateClasses : ['no_private_class' => 'No Private Institution Class Available'],
                            'Public' => !empty($publicClasses) ? $publicClasses : ['no_public_class' => 'No Public Institution Class Available'],
                        ];

                        // $tviClass = TviClass::all()->pluck('name', 'id')->toArray();
                        // return !empty($tviClass) ? $tviClass : ['no_tvi_class' => 'No Institution Class (A) Available'];
                    })
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->disableOptionWhen(fn($value) => $value === 'no_institution_class'),
                Select::make('institution_class_id')
                    ->label("Institution Class (B)")
                    ->relationship('InstitutionClass', 'name')
                    ->options(function () {
                        $institutionClass = InstitutionClass::all()->pluck('name', 'id')->toArray();
                        return !empty($institutionClass) ? $institutionClass : ['no_institution_class' => 'No Institution Class (B) Available'];
                    })
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->disableOptionWhen(fn($value) => $value === 'no_institution_class'),
                Select::make('district_id')
                    ->label('District')
                    ->options(function () {
                        return District::all()->mapWithKeys(function (District $district) {
                            $label = $district->name . ' - ' .
                                $district->municipality->name . ', ' .
                                $district->municipality->province->name;

                            return [$district->id => $label];
                        })->toArray() ?: ['no_district' => 'No District Available'];
                    })
                    ->preload()
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->searchable()
                    ->disableOptionWhen(fn($value) => $value === 'no_district'),
                TextInput::make("address")
                    ->label("Full Address")
                    ->required()
                    ->autocomplete(false)
                    ->markAsRequired(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No institutions yet')
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
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("InstitutionClass.name")
                    ->label("Institution Class(B)")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('district.name')
                    ->label('District')
                    ->getStateUsing(function ($record) {
                        $district = $record->district;

                        if (!$district) {
                            return 'No District Information';
                        }

                        $municipality = $district->municipality;
                        $province = $district->municipality->province;

                        $districtName = $district->name;
                        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
                        $provinceName = $province ? $province->name : 'Unknown Province';

                        return "{$districtName} - {$municipalityName}, {$provinceName}";
                    })
                    ->searchable()
                    ->toggleable(),
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
                    ->relationship('InstitutionClass', 'name')
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
                    ExportBulkAction::make()->exports([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTvis::route('/'),
            'create' => Pages\CreateTvi::route('/create'),
            'edit' => Pages\EditTvi::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

}
