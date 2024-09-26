<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetCommentResource\Pages;
use App\Filament\Resources\TargetCommentResource\RelationManagers;
use App\Models\TargetComment;
use Auth;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TargetCommentResource extends Resource
{
    protected static ?string $model = TargetComment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('target_id')
                    ->default(1)
                    ->readOnly(),
                TextInput::make('content'),
                TextInput::make('user_id')
                    ->default(Auth::id())
                    ->readOnly(),
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
            'index' => Pages\ListTargetComments::route('/'),
            'create' => Pages\CreateTargetComment::route('/create'),
            'edit' => Pages\EditTargetComment::route('/{record}/edit'),
        ];
    }
}
