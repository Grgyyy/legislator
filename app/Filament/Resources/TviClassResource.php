<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomInstitutionClassAExport;
use App\Filament\Resources\TviClassResource\Pages;
use App\Models\TviClass;
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

class TviClassResource extends Resource
{
    protected static ?string $model = TviClass::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?string $navigationLabel = "Institution Classes (A)";

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'institution-classes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Institution Class (A)')
                    ->placeholder('Enter institution class')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('institution class (A)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->emptyStateHeading('No institution classes available')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->label('Institution Class (A)')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter institution class a')),
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
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete institution class a')),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institution classes have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore institution class a')),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institution classes have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete institution class a')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomInstitutionClassAExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Institution Class (A)'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Classes (A)')
                        ]),
                ])
                    ->label('Select Action'),
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
