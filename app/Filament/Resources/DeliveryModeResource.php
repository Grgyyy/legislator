<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\DeliveryMode;
use App\Models\LearningMode;
use Filament\Resources\Resource;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
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
use App\Filament\Resources\DeliveryModeResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\DeliveryModeResource\RelationManagers;

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
            ->columns([
                TextColumn::make('acronym')
                    ->label('Delivery Mode Acronym'),
                TextColumn::make('name')
                ->label('Delivery Mode Name'),
            ])
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
                                        ->heading('Delivery Mode Name'),
                                    Column::make('learningMode.acronym')
                                        ->heading('Learning Mode Acronym'),
                                    Column::make('learningMode.name')
                                        ->heading('Learning Mode'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Recognitions')
                        ])
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
            'index' => Pages\ListDeliveryModes::route('/'),
            'create' => Pages\CreateDeliveryMode::route('/create'),
            'edit' => Pages\EditDeliveryMode::route('/{record}/edit'),
            'showDeliveryMode' => Pages\ShowDeliveryMode::route('/{record}/deliveryModes'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        $query->withoutGlobalScopes([SoftDeletingScope::class]);

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('learning_mode_id', (int) $routeParameter);
        }

        return $query;
    }
}
