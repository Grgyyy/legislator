<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubParticularResource\Pages;
use App\Filament\Resources\SubParticularResource\RelationManagers;
use App\Models\FundSource;
use App\Models\SubParticular;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubParticularResource extends Resource
{
    protected static ?string $model = SubParticular::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                Select::make('fund_source_id')
                    ->relationship('fundSource', 'name')
                    ->options(function () {
                        $region = FundSource::all()->pluck('name', 'id')->toArray();
                        return !empty($region) ? $region : ['no_fund_source' => 'No Fund Source Available'];
                    })
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->disableOptionWhen(fn ($value) => $value === 'no_fund_source'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('fundSource.name')
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
            'index' => Pages\ListSubParticulars::route('/'),
            'create' => Pages\CreateSubParticular::route('/create'),
            'edit' => Pages\EditSubParticular::route('/{record}/edit'),
        ];
    }
}
