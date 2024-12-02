<?php

namespace App\Filament\Clusters\Sectors\Resources;

use App\Filament\Clusters\Sectors;
use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\Pages;
use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\RelationManagers;
use App\Models\Abdd;
use App\Models\Province;
use App\Models\ProvinceAbdd;
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
                Select::make('province_id')
                    ->required()
                    ->markAsRequired(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No province available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province')
                    ->searchable(),

                Select::make('abdd_id')
                    ->required()
                    ->markAsRequired(false)
                    ->options(function () {
                        return Abdd::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_abdd' => 'No ABDD Sectors available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_abdd')
                    ->searchable(),

                TextInput::make('available_slots')
                    ->required()
                    ->markAsRequired(false)
                    ->numeric()
                    ->hidden(fn($get) => $get('record') !== null), // Hide if editing an existing record

                TextInput::make('total_slots')
                    ->required()
                    ->markAsRequired(false)
                    ->numeric(),

                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->default(date('Y'))
                    ->rules(['min:' . date('Y'), 'digits:4'])
                    ->validationAttribute('year')
                    ->validationMessages([
                        'min' => 'The allocation year must be at least ' . date('Y') . '.',
                    ]),
            ]);
    }

    


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provinces.name') 
                    ->label('Province Name'),
                TextColumn::make('abdds.name') 
                    ->label('ABDD Name'),
                TextColumn::make('available_slots') 
                    ->label('Available Slots'),
                TextColumn::make('total_slots') 
                    ->label('Total Slots'),
                TextColumn::make('year') 
                    ->label('Total Slots'),
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
