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
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
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
                TextInput::make("name")
                    ->label('Institution')
                    ->required()
                    ->autocomplete(false),
                Select::make('tvi_class_id')
                    ->label("Institution Class (A)")
                    ->relationship('tviClass', 'name')
                    ->required(),
                Select::make('institution_class_id')
                    ->label("Institution Class (B)")
                    ->relationship('InstitutionClass', 'name')
                    ->required(),
                Select::make('district_id')
                    ->label('District')
                    ->options(function () {
                        return District::all()->mapWithKeys(function (District $district) {
                            $label = $district->name . ' - ' .
                                $district->municipality->name . ', ' .
                                $district->municipality->province->name;

                            return [$district->id => $label];
                        })->toArray();
                    })
                    ->preload()
                    ->required(),
                TextInput::make("address")
                    ->label("Full Address")
                    ->required()
                    ->autocomplete(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No institutions yet')
            ->columns([
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
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
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
                                Column::make('name')
                                    ->heading('Institution Name'),
                                Column::make('tviClass.name')
                                    ->heading('Institution Class (A)'),
                                Column::make('InstitutionClass.name')
                                    ->heading('Institution Class (B)'),
                                Column::make('district.name')
                                    ->heading('District'),
                                Column::make('address')
                                    ->heading('Address'),
                                Column::make('created_at')
                                    ->heading('Date Created'),

                            ])
                            ->withFilename(date('m-d-Y') . ' - Institution')
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
