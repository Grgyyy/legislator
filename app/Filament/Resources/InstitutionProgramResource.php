<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionProgramResource\Pages;
use App\Filament\Resources\InstitutionProgramResource\RelationManagers;
use App\Models\InstitutionProgram;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstitutionProgramResource extends Resource
{
    protected static ?string $model = InstitutionProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tvi_id')
                    ->label('Institution')
                    ->relationship('tvi', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Tvi::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->mapWithKeys(function ($name, $id) {
                                $name = preg_replace_callback(
                                    '/(\d)([a-zA-Z])/',
                                    fn($matches) => $matches[1] . strtoupper($matches[2]),
                                    ucwords($name)
                                );
                                return [$id => $name];
                            })
                            ->toArray() ?: ['no_tvi' => 'No institution available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_tvi'),

                Select::make('training_program_id')
                    ->label('Training Program')
                    ->relationship('trainingProgram', 'title')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return TrainingProgram::all()
                            ->pluck('title', 'id')
                            ->mapWithKeys(function ($title, $id) {
                                $title = ucwords($title);

                                if (preg_match('/\bNC\s+[I]{1,3}\b/i', $title)) {
                                    $title = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                        return 'NC ' . strtoupper($matches[1]);
                                    }, $title);
                                }

                                return [$id => $title];
                            })
                            ->toArray() ?: ['no_training_program' => 'No Training Program Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_training_program')

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($state))),
                TextColumn::make('trainingProgram.title')
                    ->label('Training Program')
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return $state;
                        }

                        $state = ucwords($state);

                        if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                            $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                return 'NC ' . strtoupper($matches[1]);
                            }, $state);
                        }

                        return $state;
                    })
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
            'index' => Pages\ListInstitutionPrograms::route('/'),
            'create' => Pages\CreateInstitutionProgram::route('/create'),
            'edit' => Pages\EditInstitutionProgram::route('/{record}/edit'),
        ];
    }
}
