<?php

namespace App\Filament\Resources;

use App\Models\DeliveryMode;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\LearningMode;
use Filament\Resources\Resource;
use App\Services\NotificationHandler;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LearningModeResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\LearningModeResource\RelationManagers;

class LearningModeResource extends Resource
{
    protected static ?string $model = LearningMode::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Delivery Modes";

    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                Select::make('delivery_mode_id')
                    ->relationship('deliveryMode', 'name')
                    ->multiple()
                    ->options(function () {
                        return DeliveryMode::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_delivery_mode' => 'No delivery mode available'];
                    })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name'),
                TextColumn::make('deliveryMode.acronym'),
                // TextColumn::make('deliveryMode.name'),
            ])
            ->recordUrl(
                fn($record) => route('filament.admin.resources.delivery-modes.showDeliveryMode', ['record' => $record->id]),
            )
            ->filters([
                //
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
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Learning Mode'),
                                    Column::make('deliveryMode.acronym')
                                        ->heading('Delivery Mode Acronym')
                                        ->getStateUsing(function ($record) {
                                            return $record->deliveryMode->pluck('acronym')->join(', ');
                                        }),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Learning Modes')
                        ]),
                ]),
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
            'index' => Pages\ListLearningModes::route('/'),
            'create' => Pages\CreateLearningMode::route('/create'),
            'edit' => Pages\EditLearningMode::route('/{record}/edit'),
        ];
    }
}
