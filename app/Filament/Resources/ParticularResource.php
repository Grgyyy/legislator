<?php

namespace App\Filament\Resources;

use App\Models\Particular;
use App\Models\SubParticular;
use App\Models\Partylist;
use App\Models\District;
use App\Filament\Resources\ParticularResource\Pages;
use App\Services\NotificationHandler;
use Filament\Resources\Resource;
use Filament\Forms\Form;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParticularResource extends Resource
{
    protected static ?string $model = Particular::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Fund Sources";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('sub_particular_id')
                    ->label('Particular Type')
                    ->relationship('subParticular', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return SubParticular::with('fundSource')
                            ->get()
                            ->mapWithKeys(function ($subParticular) {
                                return [$subParticular->id => ($subParticular->name === 'Regular') 
                                        ? $subParticular->fundSource->name 
                                        : $subParticular->name];
                            })
                            ->toArray() ?: ['no_subparticular' => 'No Particular Type Available'];
                    })
                    ->disableOptionWhen(fn ($value) => $value === 'no_subparticular')
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('administrative_area', null);
                        
                        $administrativeArea = self::getAdministrativeAreaOptions($state);
                        $set('administrativeAreaOptions', $administrativeArea);
                        
                        if (count($administrativeArea) === 1) {
                            $set('administrative_area', key($administrativeArea));
                        }
                    })
                    ->live(),
                
                Select::make('administrative_area')
                    ->label('Administrative Area')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function ($get) {
                        $subParticularId = $get('sub_particular_id');

                        return $subParticularId
                            ? self::getAdministrativeAreaOptions($subParticularId)
                            : ['no_administrative_area' => 'No administrative area available. Select a particular type first.'];
                    })
                    ->disableOptionWhen(fn ($value) => $value === 'no_administrative_area')
                    ->reactive()
                    ->live(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no particulars available')
            ->columns([               
                TextColumn::make("subParticular.name")
                    ->label('Particular Type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make("subParticular.fundSource.name")
                    ->label('Fund Source')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                
                TextColumn::make("partylist.name")
                    ->label('Party-list')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                
                TextColumn::make("district.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                
                TextColumn::make("district.municipality.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state === 'Not Applicable' ? '-' : $state),
                
                TextColumn::make("district.municipality.province.name")
                    ->sortable()
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
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Particular has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Particular has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Particular has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected particulars have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected particulars have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected particulars have been deleted permanently.');
                        }),
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
                            ->withFilename(date('m-d-Y') . ' - Particular')
                    ]),
                ]),
            ]);
    }

    protected static function getAdministrativeAreaOptions($subParticularId) {
        $subParticular = SubParticular::find($subParticularId);
    
        if (!$subParticularId) {
            return ['no_administrative_area' => 'No Administrative Area Available'];
        }
    
        if ($subParticular->name === 'Party-list') {
            return Partylist::whereNot('name', 'Not Applicable')
                ->pluck('name', 'id')
                ->toArray() ?: ['no_administrative_area' => 'No Administrative Area Available'];
        } 
        
        if ($subParticular->fundSource->name === 'RO Regular') {
            return District::where('name', 'Not Applicable')
                ->whereHas('municipality', function ($query) {
                    $query->where('name', 'Not Applicable');
                })
                ->whereHas('municipality.province', function ($query) {
                    $query->where('name', 'Not Applicable');
                })
                ->whereHas('municipality.province.region', function ($query) {
                    $query->whereNotIn('name', ['Not Applicable', 'NCR']);
                })
                ->get()
                ->mapWithKeys(function (District $district) {
                    return [$district->id => $district->municipality->province->region->name];
                })
                ->toArray() ?: ['no_administrative_area' => 'No Administrative Area Available'];
        } 
        
        if ($subParticular->fundSource->name === 'CO Regular') {
            return District::where('name', 'Not Applicable')
                ->whereHas('municipality', function ($query) {
                    $query->where('name', 'Not Applicable');
                })
                ->whereHas('municipality.province', function ($query) {
                    $query->where('name', 'Not Applicable');
                })
                ->whereHas('municipality.province.region', function ($query) {
                    $query->where('name', 'NCR');
                })
                ->get()
                ->mapWithKeys(function (District $district) {
                    return [$district->id => $district->municipality->province->region->name];
                })
                ->toArray() ?: ['no_administrative_area' => 'No Administrative Area Available'];
        }

        if ($subParticular->name === 'Senator' || $subParticular->name === 'House Speaker' || $subParticular->name === 'House Speaker (LAKAS)') {
            return District::where('name', 'Not Applicable')
                ->whereHas('municipality', function ($query) {
                    $query->where('name', 'Not Applicable');
                })
                ->whereHas('municipality.province', function ($query) {
                    $query->where('name', 'Not Applicable');
                })
                ->whereHas('municipality.province.region', function ($query) {
                    $query->where('name', 'Not Applicable');
                })
                ->get()
                ->mapWithKeys(function (District $district) {
                    return [$district->id => $district->municipality->province->region->name];
                })
                ->toArray() ?: ['no_administrative_area' => 'No Administrative Area Available'];
        }

        if ($subParticular->name === 'District') {
            return District::whereNot('name', 'Not Applicable')
                ->get()
                ->mapWithKeys(function (District $district) {
                    $municipalityName = optional($district->municipality)->name ?? '-';
                    $provinceName = optional(optional($district->municipality)->province)->name ?? '-';
                    $regionName = optional(optional(optional($district->municipality)->province)->region)->name ?? '-';
        
                    return [
                        $district->id => $district->name 
                            . ", " . $municipalityName
                            . ", " . $provinceName
                            . ", " . $regionName
                    ];
                })
                ->toArray() ?: ['no_administrative_area' => 'No Administrative Area Available'];
        }
    
        return ['no_administrative_area' => 'No Administrative Area Available'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParticulars::route('/'),
            'create' => Pages\CreateParticular::route('/create'),
            'edit' => Pages\EditParticular::route('/{record}/edit'),
        ];
    }
}