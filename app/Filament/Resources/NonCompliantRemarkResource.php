<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NonCompliantRemarkResource\Pages;
use App\Filament\Resources\NonCompliantRemarkResource\RelationManagers;
use App\Models\NonCompliantRemark;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NonCompliantRemarkResource extends Resource
{
    protected static ?string $model = NonCompliantRemark::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('target_id')
                    ->relationship('target', 'id')
                    ->required(),
                Select::make('target_remarks_id')
                    ->relationship('remarks', 'remarks')
                    ->required(),
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
            'index' => Pages\ListNonCompliantRemarks::route('/'),
            'create' => Pages\CreateNonCompliantRemark::route('/create'),
            'edit' => Pages\EditNonCompliantRemark::route('/{record}/edit'),
        ];
    }
}
