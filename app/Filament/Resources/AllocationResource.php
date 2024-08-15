<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllocationResource\Pages;
use App\Filament\Resources\AllocationResource\RelationManagers;
use App\Models\Allocation;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Client\Request as ClientRequest;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
    return $form
        ->schema([
            Select::make('legislator_id')
                ->relationship("legislator", "name"),

                Grid::make(2)
                    ->schema([
                        TextInput::make('twsp_allocation')
                            ->label('TWSP Allocation')
                            ->required()
                            ->autocomplete(false)
                            ->numeric()
                            ->default(0)
                            ->prefix('₱')
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                $set('twsp_admin_cost', $state * 2);
                            }),
                        TextInput::make('twsp_admin_cost')
                            ->label('TWSP Admin Cost')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('₱')
                            ->minValue(0)
                            ->readOnly()
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                    ]),

                Grid::make(2)->schema([
                    TextInput::make("step_allocation")
                        ->label('STEP Allocation')
                        ->required()
                        ->autocomplete(false)
                        ->numeric()
                        ->default(0)
                        ->prefix('₱')
                        ->minValue(0)
                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $set('step_admin_cost', $state * 2);
                        }),
                    TextInput::make("step_admin_cost")
                        ->label('STEP Admin Cost')
                        ->required()
                        ->numeric()
                        ->default(0)
                        ->prefix('₱')
                        ->minValue(0)
                        ->readOnly()
                        ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("legislator.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("twsp_allocation")
                    ->label('TWSP Allocation')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),
                TextColumn::make("twsp_admin_cost")
                    ->label('TWSP Admin Cost')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),
                TextColumn::make("step_allocation")
                    ->label('STEP Allocation')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),
                TextColumn::make("step_admin_cost")
                    ->label('STEP Admin Cost')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn(\Filament\Actions\StaticAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->trashed()),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllocations::route('/'),
            'create' => Pages\CreateAllocation::route('/create'),
            'edit' => Pages\EditAllocation::route('/{record}/edit'),
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