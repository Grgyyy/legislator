<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use App\Models\InstitutionClass;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\InstitutionClassResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->required()
                    ->autocomplete(false)
                    ->label('Institution Class (B)')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No institution class yet')
            ->columns([
                TextColumn::make('name')
                    ->label('Institution Classes (B)')
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
                                    ->heading('Institution Class (B)'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Institution Class (B)')
                    ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutionClasses::route('/'),
            'create' => Pages\CreateInstitutionClass::route('/create'),
            'edit' => Pages\EditInstitutionClass::route('/{record}/edit'),
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
