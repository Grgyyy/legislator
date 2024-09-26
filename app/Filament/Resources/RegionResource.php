<?php

namespace App\Filament\Resources;

use App\Models\Region;
use App\Filament\Resources\RegionResource\Pages;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
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

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->label('Region')
                    ->placeholder('Enter region name')
                    ->required()
                    ->autocomplete(false)
                    ->validationAttribute('Region'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No regions available')
            ->columns([
                TextColumn::make("name")
                    ->label("Region")
                    ->sortable()
                    ->searchable()
                    ->url(fn($record) => route('filament.admin.resources.regions.show_provinces', ['record' => $record->id])),
            ])
            ->filters([
                TrashedFilter::make()->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed())
                        ->successNotificationTitle('Region updated successfully.')
                        ->failureNotificationTitle('Failed to update the region.'),
                    DeleteAction::make()
                        ->successNotificationTitle('Region record deleted successfully.')
                        ->failureNotificationTitle('Failed to delete the region.'),
                    RestoreAction::make()
                        ->successNotificationTitle('Region record has been restored successfully.')
                        ->failureNotificationTitle('Failed to restore the region.'),
                    ForceDeleteAction::make()
                        ->successNotificationTitle('Region record has been deleted permanently.')
                        ->failureNotificationTitle('Failed to permanently delete the region.'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Region records deleted successfully.')
                        ->failureNotificationTitle('Failed to delete region records.'),
                    ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Region records have been deleted permanently.')
                        ->failureNotificationTitle('Failed to permanently delete region records.'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Region records have been restored successfully.')
                        ->failureNotificationTitle('Failed to restore region records.'),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')->heading('Region'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Region'),
                        ]),
                ]),
            ]);
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where('name', '!=', 'Not Applicable');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
            'show_provinces' => Pages\ShowProvinces::route('/{record}/provinces'),
        ];
    }
}
