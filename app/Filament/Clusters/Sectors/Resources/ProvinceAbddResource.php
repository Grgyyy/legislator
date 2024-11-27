<?php

namespace App\Filament\Clusters\Sectors\Resources;

use App\Filament\Clusters\Sectors;
use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\Pages;
use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\RelationManagers;
use App\Models\ProvinceAbdd;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProvinceAbddResource extends Resource
{
    protected static ?string $model = ProvinceAbdd::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Sectors::class;
    protected static ?string $navigationLabel = "Province ABDD Sectors Slots";

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProvinceAbdds::route('/'),
            'create' => Pages\CreateProvinceAbdd::route('/create'),
            'edit' => Pages\EditProvinceAbdd::route('/{record}/edit'),
        ];
    }
}
