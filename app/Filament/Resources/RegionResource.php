<?php

namespace App\Filament\Resources;

use App\Models\Region;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Exports\CustomRegionExport;
use App\Services\NotificationHandler;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use App\Filament\Resources\RegionResource\Pages;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

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
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Region'),

                TextInput::make("code")
                    ->label('PSG Code')
                    ->placeholder('Enter PSG code')
                    ->autocomplete(false)
                    ->numeric()
                    ->minLength(2)
                    ->maxLength(2)
                    ->validationAttribute('PSG Code'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No regions available')
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

                            NotificationHandler::sendSuccessNotification('Deleted', 'Region has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Region has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Region has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected regions have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected regions have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected regions have been deleted permanently.');
                        }),
                    //     ExportBulkAction::make()
                    //         ->exports([
                    //             ExcelExport::make()
                    //                 ->withColumns([
                    //                     Column::make('code')
                    //                         ->heading('PSG Code')
                    //                         ->getStateUsing(function ($record) {
                    //                             return $record->code ?: '-';
                    //                         }),
                    //                     Column::make('name')
                    //                         ->heading('Region'),
                    //                 ])
                    //                 ->withFilename(date('m-d-Y') . ' - Regions'),
                    //         ]),
                    // ]),

                    // ExportBulkAction::make()
                    //     ->exports([
                    //         CustomRegionExport::make()  // Use the custom export class
                    //             ->withColumns([
                    //                 Column::make('code')
                    //                     ->heading('PSG Code')
                    //                     ->getStateUsing(function ($record) {
                    //                         return $record->code ?: '-';
                    //                     }),
                    //                 Column::make('name')
                    //                     ->heading('Region')
                    //                     ->getStateUsing(function ($record) {
                    //                         return $record->name ?: '-';
                    //                     }),
                    //             ])
                    //             ->withFilename(date('m-d-Y') . ' - Regions'),
                    //     ]),

                    ExportBulkAction::make()
                        ->exports([
                            CustomRegionExport::make()  // Use the custom export class
                                ->withColumns([
                                    Column::make('code')
                                        ->heading('PSG Code')
                                        ->getStateUsing(function ($record) {
                                            return $record->code ?: '-';
                                        }),
                                    Column::make('name')
                                        ->heading('Region')
                                        ->getStateUsing(function ($record) {
                                            return $record->name ?: '-';
                                        }),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Regions'),
                        ]),

                ]),
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
