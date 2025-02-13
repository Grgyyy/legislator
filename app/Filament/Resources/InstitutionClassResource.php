<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomInstitutionClassBExport;
use App\Filament\Resources\InstitutionClassResource\Pages;
use App\Models\InstitutionClass;
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

class InstitutionClassResource extends Resource
{
    protected static ?string $model = InstitutionClass::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?string $navigationLabel = "Institution Classes (B)";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Institution Class (B)')
                    ->placeholder(placeholder: 'Enter institution class')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Institution Class (B)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no institution class available')
            ->columns([
                TextColumn::make('name')
                    ->label('Institution Classes (B)')
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter institution class b')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Institution has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Institution has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Institution has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected institutions have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete institution class b')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institutions have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore institution class b')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institutions have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete institution class b')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomInstitutionClassBExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Institution Class (B)'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - institution_class_b_export')
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
            'index' => Pages\ListInstitutionClasses::route('/'),
            'create' => Pages\CreateInstitutionClass::route('/create'),
            'edit' => Pages\EditInstitutionClass::route('/{record}/edit'),
        ];
    }
}
