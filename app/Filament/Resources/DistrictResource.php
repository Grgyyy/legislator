<?php

namespace App\Filament\Resources;

use App\Models\District;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
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
use Filament\Tables\Actions\RestoreBulkAction;
use App\Filament\Resources\DistrictResource\Pages;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Regions";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('District')
                    ->required()
                    ->autocomplete(false),
                Select::make('municipality_id')
                    ->label('Municipality')
                    ->relationship('municipality', 'name')
                    ->default(fn($get) => request()->get('municipality_id'))
                    ->reactive()
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('District')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('municipality.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('municipality.province.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('municipality.province.region.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
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
                                    ->heading('District'),
                                Column::make('municipality.name')
                                    ->heading('Municipality'),
                                Column::make('municipality.province.name')
                                    ->heading('Province'),
                                Column::make('municipality.province.region.name')
                                    ->heading('Region'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - District')
                    ]),

                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('municipality_id', (int) $routeParameter);
        }

        return $query;
    }

    protected static function isTrashedFilterActive(): bool
    {
        $filters = request()->query('tableFilters', []);
        return isset($filters['status']['status_id']) && $filters['status']['status_id'] === 'deleted';
    }
}
