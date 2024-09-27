<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubParticularResource\Pages;
use App\Models\FundSource;
use App\Models\SubParticular;
use Filament\Forms;
use Filament\Forms\Components\Select;
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

class SubParticularResource extends Resource
{
    protected static ?string $model = SubParticular::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = "Particular Types";

    protected static ?string $navigationParentItem = "Fund Sources";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->label('Particular Type'),
                Select::make('fund_source_id')
                    ->relationship('fundSource', 'name')
                    ->options(function () {
                        $region = FundSource::all()->pluck('name', 'id')->toArray();
                        return !empty($region) ? $region : ['no_fund_source' => 'No Fund Source Available'];
                    })
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->disableOptionWhen(fn($value) => $value === 'no_fund_source'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Particular Type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('fundSource.name')
                    ->label('Fund Source')
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
                        ->successNotificationTitle('Particular Type updated successfully.')
                        ->failureNotificationTitle('Failed to update Particular Type.'),
                    DeleteAction::make()
                        ->successNotificationTitle('Particular Type deleted successfully.')
                        ->failureNotificationTitle('Failed to delete Particular Type.'),
                    RestoreAction::make()
                        ->successNotificationTitle('Particular Type restored successfully.')
                        ->failureNotificationTitle('Failed to restore Particular Type.'),
                    ForceDeleteAction::make()
                        ->successNotificationTitle('Particular Type permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete Particular Type.'),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Particular Type records deleted successfully.')
                        ->failureNotificationTitle('Failed to delete Particular Type records.'),
                    ForceDeleteBulkAction::make()
                        ->successNotificationTitle('Particular Type records permanently deleted.')
                        ->failureNotificationTitle('Failed to permanently delete Particular Type records.'),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Particular Type records restored successfully.')
                        ->failureNotificationTitle('Failed to restore Particular Type records.'),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Party Type'),
                                    Column::make('fundSource.name')
                                        ->heading('Fund Source'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Party Types'),
                        ]),
                ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubParticulars::route('/'),
            'create' => Pages\CreateSubParticular::route('/create'),
            'edit' => Pages\EditSubParticular::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
