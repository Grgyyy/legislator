<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomDeliveryModeExport;
use App\Filament\Resources\DeliveryModeResource\Pages;
use App\Models\DeliveryMode;
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
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class DeliveryModeResource extends Resource
{
    protected static ?string $model = DeliveryMode::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('acronym')
                    ->label('Delivery Mode Acronym')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label('Delivery Mode Name')
                    ->required()
                    ->maxLength(255),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('acronym')
                    ->label('Delivery Mode Acronym'),
                TextColumn::make('name')
                    ->label('Delivery Mode Name'),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.learning-modes.showLearningMode', ['record' => $record->id]),
            )
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter delivery mode')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),

                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Province has been deleted successfully.');
                        }),

                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Province has been restored successfully.');
                        }),

                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Province has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete delivery mode')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomDeliveryModeExport::make()
                                ->withColumns([
                                    Column::make('acronym')
                                        ->heading('Delivery Mode Acronym'),
                                    Column::make('name')
                                        ->heading('Delivery Mode Name'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Delivery Modes')
                        ])


                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryModes::route('/'),
            'create' => Pages\CreateDeliveryMode::route('/create'),
            'edit' => Pages\EditDeliveryMode::route('/{record}/edit'),
        ];
    }
}
