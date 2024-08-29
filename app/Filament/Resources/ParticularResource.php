<?php

namespace App\Filament\Resources;

use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Form;
use App\Models\District;
use App\Models\Particular;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\ParticularResource\Pages;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ParticularResource extends Resource
{
    protected static ?string $model = Particular::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make("name")
                    ->label('Particular')
                    ->required()
                    ->options([
                        'District' => 'District',
                        'Party List' => 'Party List',
                        'Senator' => 'Senator',
                        'Vetted' => 'Vetted',
                        'regular' => 'Regular',
                        'Star Rated' => 'Star Rated',
                        'APACC' => 'APACC',
                        'EO79' => 'EO79',
                        'EO70' => 'EO70',
                        'KIA/WIA' => 'KIA/WIA',
                        'House Speaker' => 'House Speaker',
                    ]),
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
                    ->required()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No particulars yet')
            ->columns([
                TextColumn::make("name")
                    ->label('Particular')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("district.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("district.municipality.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("district.municipality.province.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("district.municipality.province.region.name")
                    ->sortable()
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
                                    ->heading('Legislator'),
                                Column::make('district.municipality.name')
                                    ->heading('District'),
                                Column::make('district.name')
                                    ->heading('Municipality'),
                                Column::make('district.municipality.province.name')
                                    ->heading('Province'),
                                Column::make('district.municipality.province.region.name')
                                    ->heading('Region'),

                            ])
                            ->withFilename(date('m-d-Y') . ' - Particulars')
                    ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParticulars::route('/'),
            'create' => Pages\CreateParticular::route('/create'),
            'edit' => Pages\EditParticular::route('/{record}/edit'),
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
