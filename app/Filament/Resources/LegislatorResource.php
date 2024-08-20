<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ExportBulkAction as ActionsExportBulkAction;
use Filament\Forms\Form;
use App\Models\Legislator;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use App\Filament\Imports\LegislatorImporter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\LegislatorResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class LegislatorResource extends Resource
{
    protected static ?string $model = Legislator::class;
    //
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->required(),
                Select::make("particular")
                    ->multiple()
                    ->relationship("particular", "name")
                    ->required()
                    ->options(function () {
                        return \App\Models\Particular::query()
                            ->with('district')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                return [$item->id => $item->name . ' - ' . ($item->district ? $item->district->name : 'N/A') . ', ' . ($item->district->municipality ? $item->district->municipality->name : 'N/A')];
                            })
                            ->toArray();
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No legislators yet')
            ->columns([
                TextColumn::make("name")
                    ->label('Legislator')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('particular_name')
                    ->label('Particular')
                    ->getStateUsing(function ($record) {
                        $particulars = $record->particular;
                    }),
                TextColumn::make('particular_name')
                    ->label('Particular')
                    ->getStateUsing(function ($record) {
                        $particulars = $record->particular;

                        return $particulars->map(function ($particular) {
                            $municipalityName = $particular->district->name . ', ' . $particular->district->municipality->name;
                            return $particular->name . ' - ' . $municipalityName;
                        })->join(', ');
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("status")
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
                                Column::make('province.name')
                                    ->heading('Province'),
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
}
