<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkillPriorityResource\Pages;
use App\Models\Province;
use App\Models\SkillPriority;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SkillPriorityResource extends Resource
{
    protected static ?string $model = SkillPriority::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->markAsRequired(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No province available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province')
                    ->searchable(),

                Select::make('training_program_id')
                    ->label('Training Program')
                    ->required()
                    ->markAsRequired(false)
                    ->options(function () {
                        return TrainingProgram::all()
                            ->pluck('title', 'id')
                            ->toArray() ?: ['no_training_program' => 'No ABDD Sectors available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_training_program')
                    ->searchable(),

                TextInput::make('available_slots')
                    ->required()
                    ->markAsRequired(false)
                    ->numeric()
                    ->hidden(fn($livewire) => !$livewire->isEdit()), // Use the public method

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
                    ->label('Province'),
                TextColumn::make('trainingPrograms.title')
                    ->label('Training Program'),
                TextColumn::make('available_slots')
                    ->label('Available Slots'),
                TextColumn::make('total_slots')
                    ->label('Total Slots'),
                TextColumn::make('year')
                    ->label('Year'),
            ])
            ->filters([
                // Add your filters here
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Training program has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Training program has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Training program has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected training programs have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected training programs have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected training programs have been deleted permanently.');
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relations if necessary
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSkillPriorities::route('/'),
            'create' => Pages\CreateSkillPriority::route('/create'),
            'edit' => Pages\EditSkillPriority::route('/{record}/edit'),
        ];
    }
}
