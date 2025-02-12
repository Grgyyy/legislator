<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use App\Models\FundSource;
use Filament\Tables\Table;
use App\Models\SubParticular;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use App\Exports\CustomExport\CustomPartylistExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SubParticularResource\Pages;
use App\Exports\CustomExport\CustomParticularTypeExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SubParticularResource extends Resource
{
    protected static ?string $model = SubParticular::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Fund Sources";

    protected static ?string $navigationLabel = "Particular Types";

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = "particular-types";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Particular Type')
                    ->placeholder('Enter particular type')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Particular Type'),

                Select::make('fund_source_id')
                    ->label('Fund Source')
                    ->relationship('fundSource', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return FundSource::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_fund_source' => 'No fund sources available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_fund_source'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('fund_source_id')
                    ->orderBy('name');
            })
            ->emptyStateHeading('No particular types available')
            ->columns([
                TextColumn::make('name')
                    ->label('Particular Type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('fundSource.name')
                    ->label('Fund Source')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter particular types')),

                Filter::make('fundSource')
                    ->form([
                        Select::make('fund_source_id')
                            ->label("Fund Source")
                            ->placeholder('All')
                            ->relationship('fundSource', 'name')
                            ->reactive(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['fund_source_id'] ?? null,
                                fn(Builder $query, $fundSourceId) => $query->where('fund_source_id', $fundSourceId)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['fund_source_id'])) {
                            $indicators[] = 'Fund Source: ' . Optional(FundSource::find($data['fund_source_id']))->name;
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Particular type has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Particular type has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Particular type has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected particular types have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete particular types')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected particular types have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore particular types')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected particular types have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete particular types')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomParticularTypeExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Particular Type'),
                                    Column::make('fundSource.name')
                                        ->heading('Fund Source'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Particular Types'),
                        ]),
                ])
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
            'index' => Pages\ListSubParticulars::route('/'),
            'create' => Pages\CreateSubParticular::route('/create'),
            'edit' => Pages\EditSubParticular::route('/{record}/edit'),
        ];
    }
}
