<?php

namespace App\Filament\Resources;

use App\Models\Tvi;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Exports\TVIExporter;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TviResource\Pages;
use Filament\Tables\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;






class TviResource extends Resource
{
    protected static ?string $model = Tvi::class;


    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Provider';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->required()
                    ->autocomplete(false),
                TextInput::make("district")
                    ->required()
                    ->autocomplete(false),
                TextInput::make("municipality_class")
                    ->label("Municipality Class")
                    ->required()
                    ->autocomplete(false),
                Select::make('tvi_class_id')
                    ->label("TVI Class (A)")
                    ->relationship('tviClass', 'name')
                    ->required(),
                Select::make('institution_class_id')
                    ->label("TVI Class (B)")
                    ->relationship('InstitutionClass', 'name')
                    ->required(),
                TextInput::make("address")
                    ->required()
                    ->autocomplete(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("district")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("municipality_class")
                    ->label("Municipality Class")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("tviClass.name")
                    ->label('Institution Class(A)')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("InstitutionClass.name")
                    ->label("Institution Class(B)")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("address")
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
                Tables\Actions\EditAction::make()
                    ->hidden(fn($record) => $record->trashed()),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            // ->headerActions([
            //     ExportAction::make()
            //         ->exporter(TVIExporter::class)
            // ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(TVIExporter::class)
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
            'index' => Pages\ListTvis::route('/'),
            'create' => Pages\CreateTvi::route('/create'),
            'edit' => Pages\EditTvi::route('/{record}/edit'),
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
