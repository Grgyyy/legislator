<?php

namespace App\Filament\Resources;

use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Models\Particular;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\LegislatorResource\Pages;
use App\Models\Legislator;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LegislatorResource extends Resource
{
    protected static ?string $model = Legislator::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->label('Legislator')
                    ->required()
                    ->autocomplete(false),
                Select::make("particular")
                    ->multiple()
                    ->relationship("particular", "name")
                    ->required()
                    ->options(function () {
                        return Particular::query()
                            ->with('district')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                return [$item->id => $item->name . ' - ' . ($item->district ? $item->district->name : 'N/A') . ', ' . ($item->district->municipality ? $item->district->municipality->name : 'N/A')];
                            })
                            ->toArray();
                    }),
                Select::make('status_id')
                    ->label('Status')
                    ->default(1)
                    ->relationship('status', 'desc')
                    ->hidden(fn(Page $livewire) => $livewire instanceof CreateRecord),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No legislators yet')
            ->columns([
                TextColumn::make("name")
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('particular_name')
                    ->label('Particular')
                    ->getStateUsing(function ($record) {
                        $particulars = $record->particular;

                        return $particulars->map(function ($particular, $index) {
                            $municipalityName = $particular->district->name . ', ' . $particular->district->municipality->name;

                            $paddingTop = ($index > 0) ? 'padding-top: 15px;' : '';

                            return '<div style="'. $paddingTop .'">' . $particular->name . ' - ' . $municipalityName . '</div>';
                        })->implode('');
                    })
                    ->html()
                    ->toggleable(),
                TextColumn::make("status.desc")
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->relationship('status', 'desc')
                // Filter::make('status')
                //     ->form([
                //         Select::make('status_id')
                //             ->label('Status')
                //             ->relationship('status', 'desc')
                //             // ->options([
                //             //     '1' => 'Active',
                //             //     '2' => 'Inactive',
                //             // ])l
                //             ->selectablePlaceholder(false)
                //             ->live(),
                //     ])
                    // ->query(function (Builder $query, array $data): Builder {
                    //     return $query
                    //         ->when(
                    //             $data['status_id'] === 'all',
                    //             fn(Builder $query): Builder => $query->whereNull('deleted_at')
                    //         )
                    //         ->when(
                    //             $data['status_id'] === 'deleted',
                    //             fn(Builder $query): Builder => $query->whereNotNull('deleted_at')
                    //         )
                    //         ->when(
                    //             $data['status_id'] === '1',
                    //             fn(Builder $query): Builder => $query->where('status_id', 1)->whereNull('deleted_at')
                    //         )
                    //         ->when(
                    //             $data['status_id'] === '2',
                    //             fn(Builder $query): Builder => $query->where('status_id', 2)->whereNull('deleted_at')
                    //         );
                    // }),
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
                    DeleteBulkAction::make(),
                        // ->hidden(fn (): bool => self::isTrashedFilterActive()),
                    ForceDeleteBulkAction::make(),
                        // ->hidden(fn (): bool => !self::isTrashedFilterActive()),
                    RestoreBulkAction::make(),
                        // ->hidden(fn (): bool => !self::isTrashedFilterActive()),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Legislator'),
                                    Column::make('formatted_particular')
                                        ->heading('Particular'),
                                    // Column::make('formatted_district')
                                    //     ->heading('District'),

                                ])
                        ->withFilename(date('m-d-Y') . ' - Legislator')
                    ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegislators::route('/'),
            'create' => Pages\CreateLegislator::route('/create'),
            'edit' => Pages\EditLegislator::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // protected static function isTrashedFilterActive(): bool
    // {
    //     $filters = request()->query('tableFilters', []);
    //     return isset($filters['status']['status_id']) && $filters['status']['status_id'] === 'deleted';
    // }
}
