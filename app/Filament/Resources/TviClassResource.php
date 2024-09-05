<?php

namespace App\Filament\Resources;

use App\Models\TviClass;
use Filament\Forms\Form;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\TviClassResource\Pages;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class TviClassResource extends Resource
{
    protected static ?string $model = TviClass::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?string $navigationLabel = "Institution Classes (A)";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Institution Class (A)')
                    ->required()
                    ->autocomplete(false),
                Select::make('tvi_type_id')
                    ->label('Institution Type')
                    ->relationship('tviType', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No institution class yet')
            ->columns([
                TextColumn::make('name')
                    ->Label('Institution Classes (A)')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tviType.name')
                    ->label('Institution Types')
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
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')
                                    ->heading('Institution Class (A)'),
                                Column::make('tviType.name')
                                    ->heading('Institution Type'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Institution Class (A)')
                    ]),

                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTviClasses::route('/'),
            'create' => Pages\CreateTviClass::route('/create'),
            'edit' => Pages\EditTviClass::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class
            ]);
    }
}
