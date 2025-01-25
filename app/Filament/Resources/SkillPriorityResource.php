<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Province;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SkillPriority;
use PhpParser\Node\Stmt\Label;
use App\Models\TrainingProgram;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Filament\Support\Enums\MaxWidth;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use App\Filament\Resources\SkillPriorityResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SkillPriorityResource extends Resource
{
    protected static ?string $model = SkillPriority::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = "Skill Priorities";

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

                TextInput::make('available_slots')
                    ->label('Available Slots')
                    ->required()
                    ->markAsRequired(false)
                    ->integer()
                    ->hidden(fn($livewire) => !$livewire->isEdit()),

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
            ->actions([
                ActionGroup::make([
                    // Action::make('addSlots')
                    //     ->modalContent(function (SkillPriority $record): HtmlString {
                    //         return new HtmlString("
                    //             <div style='margin-bottom: 1rem; margin-top: 1rem; font-size: .9rem; display: grid; grid-template-columns: 1fr 2fr; gap: 10px;' >
                    //                 <div style='font-weight: bold;'>Province:</div>
                    //                 <div>{$record->provinces->name}</div>
                    //                 <div style='font-weight: bold;'>Training Program:</div>
                    //                 <div>{$record->trainingPrograms->title}</div>
                    //                 <div style='font-weight: bold;'>Available Slots:</div>
                    //                 <div>{$record->available_slots}</div>
                    //                 <div style='font-weight: bold;'>Total Slots:</div>
                    //                 <div>{$record->total_slots}</div>
                    //                 <div style='font-weight: bold;'>Year:</div>
                    //                 <div>{$record->year}</div>
                    //             </div>
                    //         ");
                    //     })
                    //     ->modalHeading('Add Slots')
                    //     ->modalWidth(MaxWidth::TwoExtraLarge)
                    //     ->icon('heroicon-o-plus')
                    //     ->label('Add Slots')
                    //     ->form([
                    //         TextInput::make('available_slots')
                    //             ->label('Add Slots')
                    //             ->autocomplete(false)
                    //             ->integer()
                    //             ->default(0)
                    //             ->minValue(0)
                    //     ])
                    //     ->action(function (array $data, SkillPriority $record): void {
                    //         $record->available_slots += $data['available_slots'];
                    //         $record->total_slots += $data['available_slots'];
                    //         $record->save();
                    //         NotificationHandler::sendSuccessNotification('Saved', 'Skill priority slots have been added successfully.');
                    //     }),
                    // DeleteAction::make()
                    //     ->action(function ($record, $data) {
                    //         $record->delete();
                    //         NotificationHandler::sendSuccessNotification('Deleted', 'Training program has been deleted successfully.');
                    //     }),
                    // RestoreAction::make()
                    //     ->action(function ($record, $data) {
                    //         $record->restore();
                    //         NotificationHandler::sendSuccessNotification('Restored', 'Training program has been restored successfully.');
                    //     }),
                    // ForceDeleteAction::make()
                    //     ->action(function ($record, $data) {
                    //         $record->forceDelete();
                    //         NotificationHandler::sendSuccessNotification('Force Deleted', 'Training program has been deleted permanently.');
                    //     }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected Skills Priority have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected Skills Priority have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected Skills Priority have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('provinces.name')
                                        ->heading('Province'),
                                    Column::make('trainingPrograms.title')
                                        ->heading('Training Program Title'),
                                    Column::make('available_slots')
                                        ->heading('Available Slots'),
                                    Column::make('total_slots')
                                        ->heading('Total Slots'),
                                    Column::make('year')->heading('Year'),
                                ])
                                ->withFilename(date('Y-m-d') . '-skill-priorities-export.xlsx'),
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
