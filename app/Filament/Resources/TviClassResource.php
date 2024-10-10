<?php

namespace App\Filament\Resources;

use App\Models\TviClass;
use App\Models\TviType;
use App\Filament\Resources\TviClassResource\Pages;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->placeholder(placeholder: 'Enter institution class')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Institution Class (A)'),

                Select::make('tvi_type_id')
                    ->label('Institution Type')
                    ->relationship('tviType', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return TviType::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_tvi_type' => 'No Institution Type Available'];
                    })
                    ->disableOptionWhen(fn ($value) => $value === 'no_tvi_type'),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no institution classes available')
            ->columns([
                TextColumn::make('name')
                    ->Label('Institution Class (A)')
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

                SelectFilter::make('tvi_type_id')
                    ->label('Institution Type')
                    ->relationship('tviType', 'name')
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
                                        ->heading('Institution Class (A)'),
                                    Column::make('tviType.name')
                                        ->heading('Institution Type'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Class (A)')
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
            'index' => Pages\ListTviClasses::route('/'),
            'create' => Pages\CreateTviClass::route('/create'),
            'edit' => Pages\EditTviClass::route('/{record}/edit'),
        ];
    }
}
