<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetRemarkResource\Pages;
use App\Filament\Resources\TargetRemarkResource\RelationManagers;
use App\Models\TargetRemark;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TargetRemarkResource extends Resource
{
    protected static ?string $model = TargetRemark::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    
    protected static ?string $navigationGroup = "MANAGE TARGET";
    protected static ?string $navigationLabel = "Target Remarks";

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('remarks')
                    ->required()
                    ->markAsRequired(false)
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('remarks')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTargetRemarks::route('/'),
            'create' => Pages\CreateTargetRemark::route('/create'),
            'edit' => Pages\EditTargetRemark::route('/{record}/edit'),
        ];
    }
}
