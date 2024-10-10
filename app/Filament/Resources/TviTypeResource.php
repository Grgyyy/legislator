<?php

namespace App\Filament\Resources;

use App\Models\TviType;
use App\Filament\Resources\TviTypeResource\Pages;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TviTypeResource extends Resource
{
    protected static ?string $model = TviType::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?string $navigationLabel = "Institution Types";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Institution Type')
                    ->placeholder(placeholder: 'Enter institution type')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Institution Type'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no institution types available')
            ->columns([
                TextColumn::make('name')
                    ->label('Institution Types')
                    ->sortable()
                    ->searchable(),
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
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Institution Type'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Class (B)')
                        ]),
                ]),
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
            'index' => Pages\ListTviTypes::route('/'),
            'create' => Pages\CreateTviType::route('/create'),
            'edit' => Pages\EditTviType::route('/{record}/edit'),
        ];
    }
}