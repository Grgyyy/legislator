<?php

namespace App\Filament\Resources;

use App\Models\TviType;
use App\Models\TviClass;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use App\Filament\Resources\TviClassResource\Pages;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TviClassResource extends Resource
{
    protected static ?string $model = TviClass::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?string $navigationLabel = "Institution Classes (A)";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Institution Class (A)')
                    ->placeholder(placeholder: 'Enter institution class')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Institution Class (A)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no institution classes available')
            ->columns([
                TextColumn::make('name')
                    ->Label('Institution Class (A)')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

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

                            NotificationHandler::sendSuccessNotification('Deleted', 'Institution class has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Institution class has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Institution class has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected institution classes have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete institution class b')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institution classes have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore institution class b')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institution classes have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete institution class b')),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Institution Class (A)'),
                                    Column::make('tviType.name')
                                        ->heading('Institution Type'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Class (A)')
                        ]),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTviClasses::route('/'),
            'create' => Pages\CreateTviClass::route('/create'),
            'edit' => Pages\EditTviClass::route('/{record}/edit'),
        ];
    }
}
