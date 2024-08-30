<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriorityResource\Pages;
use App\Filament\Resources\PriorityResource\RelationManagers;
use App\Models\Priority;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PriorityResource extends Resource
{
    protected static ?string $model = Priority::class;

    protected static ?string $navigationLabel = "Ten Priority Sectors";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Sector Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('status')
                ->form([
                    Select::make('status_id')
                        ->label('Status')
                        ->options([
                            'all' => 'All',
                        'deleted' => 'Recently Deleted',
                        ])
                        ->default('all')
                        ->selectablePlaceholder(false),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['status_id'] === 'all',
                            fn (Builder $query): Builder => $query->whereNull('deleted_at')
                        )
                        ->when(
                            $data['status_id'] === 'deleted',
                            fn (Builder $query): Builder => $query->whereNotNull('deleted_at')
                        );
                }),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriorities::route('/'),
            'create' => Pages\CreatePriority::route('/create'),
            'edit' => Pages\EditPriority::route('/{record}/edit'),
        ];
    }
}
