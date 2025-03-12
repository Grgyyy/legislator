<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomRegionExport;
use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use App\Services\NotificationHandler;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                    ->minLength(10)
                    ->maxLength(10)
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('region'),

                TextInput::make("code")
                    ->label('PSG Code')
                    ->placeholder('Enter PSG code')
                    ->autocomplete(false)
                    ->numeric()
                    ->minLength(2)
                    ->maxLength(2)
                    ->currencyMask(precision: 0)
                    ->validationAttribute('PSG code'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No regions available')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make("code")
                    ->label("PSG Code")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->code ?? '-'),

                TextColumn::make("name")
                    ->label("Region")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.provinces.showProvince', ['record' => $record->id]),
            )
            ->filters([
                TrashedFilter::make()->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter region ')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record) {
                            $record->delete();
                            NotificationHandler::sendSuccessNotification('Deleted', 'Region has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record) {
                            $record->restore();
                            NotificationHandler::sendSuccessNotification('Restored', 'Region has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record) {
                            $record->forceDelete();
                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Region has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(fn($records) => $records->each->delete()),

                    RestoreBulkAction::make()
                        ->action(fn($records) => $records->each->restore()),

                    ForceDeleteBulkAction::make()
                        ->action(fn($records) => $records->each->forceDelete()),

                    ExportBulkAction::make()
                        ->exports([
                            CustomRegionExport::make()
                                ->withColumns([
                                    Column::make('code')
                                        ->heading('PSG Code')
                                        ->getStateUsing(fn($record) => $record->code ?: '-'),

                                    Column::make('name')
                                        ->heading('Region')
                                        ->getStateUsing(fn($record) => $record->name ?: '-'),
                                ])
                                ->withFilename(now()->format('m-d-Y') . ' - Regions'),
                        ]),
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNot('name', 'Not Applicable');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
