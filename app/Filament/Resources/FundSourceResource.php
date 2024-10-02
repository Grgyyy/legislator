<?php

namespace App\Filament\Resources;

use App\Models\FundSource;
use App\Filament\Resources\FundSourceResource\Pages;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class FundSourceResource extends Resource
{
    protected static ?string $model = FundSource::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Fund Source')
                    ->placeholder(placeholder: 'Enter fund source')
                    ->required()
                    ->autocomplete(false)
                    ->validationAttribute('Fund Source'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No fund sources available')
            ->columns([
                TextColumn::make("name")
                    ->label('Fund Source')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed())
                        ->successNotificationTitle('Fund Source updated successfully.')
                        ->failureNotificationTitle('Failed to update the Fund Source.'),
                    DeleteAction::make()
                        ->successNotificationTitle('Fund Source deleted successfully.')
                        ->failureNotificationTitle('Failed to delete the Fund Source.'),
                    RestoreAction::make()
                        ->successNotificationTitle('Fund Source restored successfully.')
                        ->failureNotificationTitle('Failed to restore the Fund Source.'),
                    ForceDeleteAction::make()
                        ->successNotificationTitle('Fund Source permanently deleted successfully.')
                        ->failureNotificationTitle('Failed to permanently delete the Fund Source.'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Fund Sources deleted successfully.')
                        ->failureNotificationTitle('Failed to delete Fund Sources.'),
                    ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Fund Sources permanently deleted successfully.')
                        ->failureNotificationTitle('Failed to permanently delete Fund Sources.'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Fund Sources restored successfully.')
                        ->failureNotificationTitle('Failed to restore Fund Sources.'),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Fund Source'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Fund Source'),
                        ]),
                ])
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundSources::route('/'),
            'create' => Pages\CreateFundSource::route('/create'),
            'edit' => Pages\EditFundSource::route('/{record}/edit'),
        ];
    }
}