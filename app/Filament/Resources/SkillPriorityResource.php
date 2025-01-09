<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkillPriorityResource\Pages;
use App\Models\Province;
use App\Models\SkillPriority;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class SkillPriorityResource extends Resource
{
    protected static ?string $model = SkillPriority::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No provinces available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province'),

                Select::make('training_program_id')
                    ->label('Training Program')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return TrainingProgram::all()
                            ->pluck('title', 'id')
                            ->toArray() ?: ['no_training_program' => 'No training programs available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_training_program'),

                // TextInput::make('available_slots')
                //     ->label('Available Slots')
                //     ->required()
                //     ->markAsRequired(false)
                //     ->integer()
                //     ->hidden(fn($livewire) => !$livewire->isEdit()),

                TextInput::make('total_slots')
                    ->label('Slots')
                    ->placeholder('Enter number of slots')
                    ->required()
                    ->markAsRequired(false)
                    ->integer()
                    ->disabled(fn($livewire) => $livewire->isEdit())
                    ->dehydrated(),

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
                    ->searchable()
                    ->label('Training Program')
                    // ->formatStateUsing(function ($state) {
                    //     if (!$state) {
                    //         return $state;
                    //     }

                    //     $state = ucwords($state);

                    //     if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                    //         $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                    //             return 'NC ' . strtoupper($matches[1]);
                    //         }, $state);
                    //     }

                    //     return $state;
                    // })
                    ,
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
                    Action::make('addSlots')
                        ->modalContent(function (SkillPriority $record): HtmlString {
                            return new HtmlString("
                                <div style='margin-bottom: 1rem; margin-top: 1rem; font-size: .9rem; display: grid; grid-template-columns: 1fr 2fr; gap: 10px;'>
                                    <div style='font-weight: bold;'>Province:</div>
                                    <div>{$record->provinces->name}</div>
                                    <div style='font-weight: bold;'>Training Program:</div>
                                    <div>{$record->trainingPrograms->title}</div>
                                    <div style='font-weight: bold;'>Available Slots:</div>
                                    <div>{$record->available_slots}</div>
                                    <div style='font-weight: bold;'>Total Slots:</div>
                                    <div>{$record->total_slots}</div>
                                    <div style='font-weight: bold;'>Year:</div>
                                    <div>{$record->year}</div>
                                </div>
                            ");
                        })
                        ->modalHeading('Add Slots')
                        ->modalWidth(MaxWidth::TwoExtraLarge)
                        ->icon('heroicon-o-plus')
                        ->label('Add Slots')
                        ->form([
                            TextInput::make('available_slots')
                                ->label('Add Slots')
                                ->autocomplete(false)
                                ->integer()
                                ->default(0)
                                ->minValue(0)
                        ])
                        ->action(function (array $data, SkillPriority $record): void {
                            $record->available_slots += $data['available_slots'];
                            $record->total_slots += $data['available_slots'];
                            $record->save();
                            NotificationHandler::sendSuccessNotification('Saved', 'Skill priority slots have been added successfully.');
                        }),
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
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('province.name')
                                        ->heading('Province')
                                        ->getStateUsing(function ($record) {
                                            $province = $record->provinces;
                                            return $province ? $province->name : 'No province available';
                                        }),

                                    Column::make('trainingProgram.title')
                                        ->heading('Qualification Title')
                                        ->getStateUsing(function ($record) {
                                            $trainingProgram = $record->trainingPrograms;
                                            return $trainingProgram ? $trainingProgram->title : 'No Training Program available';
                                        }),

                                    Column::make('available_slots')
                                        ->heading('Available Slots'),

                                    Column::make('total_slots')
                                        ->heading('Total Slots'),

                                    Column::make('year')
                                        ->heading('Year'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Skill Priorities')
                        ])
                ]),
            ]);
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