<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomRecognitionExport;
use App\Filament\Resources\RecognitionResource\Pages;
use App\Models\Recognition;
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
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class RecognitionResource extends Resource
{
    protected static ?string $model = Recognition::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Recognition Titles';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Recognition Title')
                    ->placeholder('Enter recognition Title')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('recognition title'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->emptyStateHeading('No recognition titles available')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->label("Recoginition Title")
                    ->sortable()
                    ->searchable()
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter recognitions')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),

                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Recognition Title has been deleted successfully.');
                        }),

                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Recognition Title has been restored successfully.');
                        }),

                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Recognition Title has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected recognition have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete recognitions')),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected recognition have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore recognitions')),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected recognition have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete recognitions')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomRecognitionExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Recognition Title'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Recognition Titles')
                        ]),
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecognitions::route('/'),
            'create' => Pages\CreateRecognition::route('/create'),
            'edit' => Pages\EditRecognition::route('/{record}/edit'),
        ];
    }
}
