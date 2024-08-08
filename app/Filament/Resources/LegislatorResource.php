<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegislatorResource\Pages;
use App\Filament\Resources\LegislatorResource\RelationManagers;
use App\Models\Legislator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction as ActionsExportBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class LegislatorResource extends Resource
{
    protected static ?string $model = Legislator::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make("name")
                    ->required(),
                Select::make("particular")
                    ->relationship("particular", "name")
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label('Legislator')
                    ->sortable()
                    ->searchable(),  
                TextColumn::make("particular.name"),
                TextColumn::make("particular.province.name"),
                TextColumn::make("particular.province.region.name"),    
                TextColumn::make("status"),   
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn (\Filament\Actions\StaticAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), 
                Tables\Actions\RestoreAction::make(), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(), 
                    Tables\Actions\RestoreBulkAction::make(), 
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                        ->withColumns([
                            Column::make('name')->heading('Region Name'),
                            Column::make('created_at')->heading('Date Created'),
                        ])
                        ->withFilename(date('Y-m-d') . ' - Legislators')
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
