<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomLearningModeExport;
use App\Filament\Resources\LearningModeResource\Pages;
use App\Filament\Resources\LearningModeResource\RelationManagers;
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
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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

                TextColumn::make('name')
                    ->label('Learning Mode'),
                TextColumn::make('deliveryMode.acronym')
                    ->label('Delivery Mode Acronym'),
                // TextColumn::make('deliveryMode.name'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter learning mode')),
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
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete learning mode ')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomLearningModeExport::make()
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
            'index' => Pages\ListLearningModes::route('/'),
            'create' => Pages\CreateLearningMode::route('/create'),
            'edit' => Pages\EditLearningMode::route('/{record}/edit'),
            'showLearningMode' => Pages\ShowLearningMode::route('/{record}/learningMode')
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery();

    //     // Remove global soft delete scope to include trashed records
    //     $query->withoutGlobalScopes([SoftDeletingScope::class]);

    //     $routeParameter = request()->route('record');

    //     // Apply filter only if a valid delivery_mode_id is present in the route
    //     if ($routeParameter && is_numeric($routeParameter)) {
    //         $query->whereHas('deliveryMode', function ($subQuery) use ($routeParameter) {
    //             $subQuery->where('delivery_modes.id', (int) $routeParameter);
    //         });
    //     }

    //     return $query;
    // }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        if (request()->routeIs('filament.resources.learning-modes.index') && request()->has('delivery_mode')) {
            $deliveryMode = request()->query('delivery_mode');
            $query->whereHas('deliveryMode', function ($subQuery) use ($deliveryMode) {
                $subQuery->where('delivery_modes.id', $deliveryMode);
            });
        }

        return $query;
    }

}
