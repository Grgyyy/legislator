<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScholarshipProgramResource\Pages;
use App\Filament\Resources\ScholarshipProgramResource\RelationManagers;
use App\Models\ScholarshipProgram;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScholarshipProgramResource extends Resource
{
    protected static ?string $model = ScholarshipProgram::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('name')
                    ->label("Scholarship Program")
                    ->options([
                        'STEP' => 'STEP',
                        'TWSP' => 'TWSP',
                        'TTSP' => 'TTSP',
                        'UAQTEA' => 'UAQTEA'
                    ])
                    ->required(),
                TextInput::make("qualification_title")
                    ->label('Qualification Title')
                    ->required(),
                TextInput::make("training_cost")
                    ->label('Training Cost PCC')
                    ->numeric()
                    ->required(),
                TextInput::make("toolkit_cost")
                    ->label('Cost of Toolkit PCC')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("name")
                    ->label("Scholarship Program")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("qualification_title")
                    ->label("Qualification Title")
                    ->sortable()
                    ->searchable(),
                TextColumn::make("training_cost")
                    ->label("Training Cost PCC")
                    ->sortable()
                    ->formatStateUsing(fn ($state) => '₱ ' . $state),
                TextColumn::make("toolkit_cost")
                    ->label("Cost of Toolkit PCC")
                    ->sortable()
                    ->formatStateUsing(fn ($state) => '₱ ' . $state),
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
            'index' => Pages\ListScholarshipPrograms::route('/'),
            'create' => Pages\CreateScholarshipProgram::route('/create'),
            'edit' => Pages\EditScholarshipProgram::route('/{record}/edit'),
        ];
    }
}
