<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Form;
use App\Models\Particular;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\ParticularResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ParticularResource extends Resource
{
    protected static ?string $model = Particular::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->required()
                    ->autocomplete(false),
                Select::make('municipality_id')
                    ->label("Municipality")
                    ->relationship("municipality", "name")
                    ->required()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No particulars yet')
            ->columns([
                TextColumn::make("name")
                    ->label('Particular Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("municipality.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("municipality.province.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("municipality.province.region.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
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
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')
                                    ->heading('Legislator Name'),
                                Column::make('municipality.name')
                                    ->heading('Municipality'),
                                Column::make('municipality.province.name')
                                    ->heading('Province'),
                                Column::make('municipality.province.region.name')
                                    ->heading('Region'),

                            ])
                            ->withFilename(date('m-d-Y') . ' - Municipality')
                    ]),
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
            'index' => Pages\ListParticulars::route('/'),
            'create' => Pages\CreateParticular::route('/create'),
            'edit' => Pages\EditParticular::route('/{record}/edit'),
        ];
    }
}
