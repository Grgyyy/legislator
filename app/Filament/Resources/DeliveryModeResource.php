<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryModeResource\Pages;
use App\Filament\Resources\DeliveryModeResource\RelationManagers;
use App\Models\DeliveryMode;
use App\Models\LearningMode;
use App\Services\NotificationHandler;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveryModeResource extends Resource
{
    protected static ?string $model = DeliveryMode::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Learning Modes";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Delivery Mode Name')
                    ->required()
                    ->maxLength(255),
                Select::make('learning_mode_id')
                    ->label('Learning Mode')
                    ->relationship('learningMode', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->placeholder('Select a Learning Mode')
                    ->options(function () {
                        return LearningMode::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_learning_mode' => 'No learning mode available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_learning_mode'),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('learningMode.acronym')
                    ->label('Learning Mode Acronym'),
                TextColumn::make('learningMode.name')
                    ->label('Learning Mode Name'),
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
