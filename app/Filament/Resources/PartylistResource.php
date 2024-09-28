<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartylistResource\Pages;
use App\Models\Partylist;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class PartylistResource extends Resource
{
    protected static ?string $model = Partylist::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = "Party-List";

    protected static ?string $navigationParentItem = "Fund Sources";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->label('Party-List')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Party-List')
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
                        ->hidden(fn($record) => $record->trashed())
                        ->successNotificationTitle('Partylist updated successfully.')
                        ->failureNotificationTitle('Failed to update Partylist.'),
                    DeleteAction::make()
                        ->successNotificationTitle('Partylist deleted successfully.')
                        ->failureNotificationTitle('Failed to delete Partylist.'),
                    RestoreAction::make()
                        ->successNotificationTitle('Partylist restored successfully.')
                        ->failureNotificationTitle('Failed to restore Partylist.'),
                    ForceDeleteAction::make()
                        ->successNotificationTitle('Partylist permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete Partylist.'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Partylist records deleted successfully.')
                        ->failureNotificationTitle('Failed to delete partylist records.'),
                    ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Partylist records permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete partylist records.'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Partylist records restored successfully.')
                        ->failureNotificationTitle('Failed to restore partylist records.'),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Party-List'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Party-List'),
                        ]),
                ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartylists::route('/'),
            'create' => Pages\CreatePartylist::route('/create'),
            'edit' => Pages\EditPartylist::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
