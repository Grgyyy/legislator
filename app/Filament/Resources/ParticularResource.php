<?php

namespace App\Filament\Resources;

use App\Models\Partylist;
use App\Models\SubParticular;
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
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\ParticularResource\Pages;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\Filter;
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
                Select::make('sub_particular_id')
                    ->label('Sub-Particular')
                    ->options(function () {
                        return SubParticular::all()->pluck('name', 'id');
                    })
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $set('partylist_district', null); 
                    }),
                
                Select::make('partylist_district')
                    ->label('District/Partylist')
                    ->searchable()
                    ->options(function ($get) {
                        $subParticularId = $get('sub_particular_id');
                        return $subParticularId ? self::getOptions($subParticularId) : ['' => 'No Sub Particular Available.'];
                    })
            ]);
    }

    
    protected static function getOptions($subParticularId) {
        $subParticular = SubParticular::find($subParticularId);
    
        if ($subParticular) {
            if ($subParticular->name === 'Partylist') {
                return Partylist::all()->pluck('name', 'id')->toArray();
            } else {
                return District::all()->mapWithKeys(function (District $district) {
                    return [$district->id => $district->name . ' - ' . $district->municipality->name];
                })->toArray();
            }
        }
    
        return ['' => 'No Options Available.'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No particulars yet')
            ->columns([
                TextColumn::make("subParticular.fundSource.name")
                    ->label('Fund Source')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                TextColumn::make("subParticular.name")
                    ->label('Particular')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                TextColumn::make("district.name")
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                TextColumn::make("district.municipality.name")
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                TextColumn::make("district.municipality.province.name")
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                TextColumn::make("district.municipality.province.region.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
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
