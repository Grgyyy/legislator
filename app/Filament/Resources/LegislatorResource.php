<?php

namespace App\Filament\Resources;

use Filament\Tables;
<<<<<<< HEAD
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ExportBulkAction as ActionsExportBulkAction;
=======
use Filament\Forms\Form;
use App\Models\Legislator;
>>>>>>> cc2605e (Feat: Created export features using pxlrbt plugins)
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
<<<<<<< HEAD
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
=======
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Imports\LegislatorImporter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LegislatorResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
>>>>>>> cc2605e (Feat: Created export features using pxlrbt plugins)

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
                            ->with('municipality')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                return [$item->id => $item->name . ' - ' . ($item->municipality ? $item->municipality->name : 'N/A')];
                            })
                            ->toArray();
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
<<<<<<< HEAD
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
                    // Assuming `particular` is a many-to-many relationship and it's a collection
                    $particulars = $record->particular;
=======
            ->columns([
                TextColumn::make("name")
                    ->label('Legislator')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
>>>>>>> cc2605e (Feat: Created export features using pxlrbt plugins)

                TextColumn::make('particular_name')
                    ->label('Particular')
                    ->getStateUsing(function ($record) {
                        $particulars = $record->particular;

                        return $particulars->map(function ($particular) {
                            $municipalityName = $particular->municipality ? $particular->municipality->name : 'N/A';
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
                        ->hidden(fn ($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
<<<<<<< HEAD
=======
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')
                                    ->heading('Legislator Name'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Legislator')
                    ]),
>>>>>>> cc2605e (Feat: Created export features using pxlrbt plugins)
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
