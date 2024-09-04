<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Tvet;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\TvetResource\Pages;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TvetResource\RelationManagers;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TvetResource extends Resource
{
    protected static ?string $model = Tvet::class;

    protected static ?string $navigationLabel = "TVET Sectors";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Sector')
                    ->required()
                    ->validationAttribute('sector'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No sectors yet')
            ->columns([
                TextColumn::make('name')
                    ->label("Sector")
                    ->searchable()
                    ->sortable()
            ])
            ->filters([
                Filter::make('status')
                    ->form([
                        Select::make('status_id')
                            ->label('Status')
                            ->options([
                                'all' => 'All',
                                'deleted' => 'Recently Deleted',
                            ])
                            ->default('all')
                            ->selectablePlaceholder(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['status_id'] === 'all',
                                fn(Builder $query): Builder => $query->whereNull('deleted_at')
                            )
                            ->when(
                                $data['status_id'] === 'deleted',
                                fn(Builder $query): Builder => $query->whereNotNull('deleted_at')
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn (): bool => self::isTrashedFilterActive()),
                    ForceDeleteBulkAction::make()
                        ->hidden(fn (): bool => !self::isTrashedFilterActive()),
                    RestoreBulkAction::make()
                        ->hidden(fn (): bool => !self::isTrashedFilterActive()),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')
                                    ->heading('TVET Sector'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - TVET Sector')
                    ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTvets::route('/'),
            'create' => Pages\CreateTvet::route('/create'),
            'edit' => Pages\EditTvet::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function isTrashedFilterActive(): bool
    {
        $filters = request()->query('tableFilters', []);
        return isset($filters['status']['status_id']) && $filters['status']['status_id'] === 'deleted';
    }
}
