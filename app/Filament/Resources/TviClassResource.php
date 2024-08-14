<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\TviClass;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\TviClassResource\Pages;
use pxlrbt\FilamentExcel\Exports\Concerns\WithFilename;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TviClassResource extends Resource
{
    protected static ?string $model = TviClass::class;
    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "TVI";
    protected static ?string $navigationLabel = "TVI Classes (A)";
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('TVI Class (A)')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('tvi_type_id')
                    ->label('TVI Type')
                    ->relationship('tviType', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->Label('TVI Classes (A)')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tviType.name')
                    ->label('TVI Types')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn(StaticAction $action) => $action
                    ->button()
                    ->label('Filter')
            )
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('TVI Class (A)'),
                                    Column::make('tviType.name')
                                        ->heading('TVI Type'),
                                    Column::make('created_at')
                                        ->heading('Date Created'),
                                ])->WithFilename(date('m-d-Y') . '- TVI Class (A)'),
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
            'index' => Pages\ListTviClasses::route('/'),
            'create' => Pages\CreateTviClass::route('/create'),
            'edit' => Pages\EditTviClass::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
