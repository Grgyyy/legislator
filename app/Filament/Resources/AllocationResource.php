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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('legislator_id')
                    ->relationship("legislator", "legislator_name"),

                Grid::make(2)->schema([
                    TextInput::make("twsp_allocation")->required(),
                    TextInput::make("twsp_admin_cost")->required(),
                ]),
                
                Grid::make(2)->schema([
                    TextInput::make("step_allocation")->required(),
                    TextInput::make("step_admin_cost")->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("legislator.legislator_name"),
                TextColumn::make("twsp_allocation")
                    ->label('TWSP Allocation'),
                TextColumn::make("twsp_admin_cost")
                    ->label('TWSP Admin Cost'),
                TextColumn::make("step_allocation")
                    ->label('STEP Allocation'),
                TextColumn::make("step_admin_cost")
                    ->label('STEP Admin Cost'),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
