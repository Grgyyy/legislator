<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ToolkitResource\Pages;
use App\Filament\Resources\ToolkitResource\RelationManagers;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\Toolkit;
use App\Services\NotificationHandler;
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
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ToolkitResource extends Resource
{
    protected static ?string $model = Toolkit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('qualification_title_id')
                    ->label('Qualification Title')
                    ->searchable()
                    ->options(function () {
                        $step = ScholarshipProgram::where('name', 'STEP')->first();

                        if (!$step) {
                            return ['no_step' => 'No STEP Scholarship Program available'];
                        }

                        $qualificationTitles = QualificationTitle::whereNull('deleted_at')
                            ->where('scholarship_program_id', $step->id)
                            ->with('trainingProgram')
                            ->get()
                            ->filter(fn($qualification) => $qualification->trainingProgram)
                            ->mapWithKeys(function ($qualification) {
                                $title = $qualification->trainingProgram->title;

                                $title = preg_replace_callback(
                                    '/\bNC\s+([I]{1,3})\b/i',
                                    fn($matches) => 'NC ' . strtoupper($matches[1]),
                                    $title
                                );

                                return [$qualification->id => ucwords($title)];
                            })
                            ->toArray();

                        return $qualificationTitles ?: ['no_available' => 'No available Qualification Titles'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_step' || $value === 'no_available'),

                TextInput::make('price_per_toolkit')
                    ->label('Price Per Toolkit')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->prefix('₱')
                    ->default(0)
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                TextInput::make('number_of_toolkit')
                    ->label('Number of Toolkits')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->disabled(fn($livewire) => $livewire->isEdit())
                    ->dehydrated(),

                TextInput::make('number_of_items_per_toolkit')
                    ->label('Number of Items per Toolkit')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->prefix('₱')
                    ->default(0)
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->default(date('Y'))
                    ->rules(['min:' . date('Y'), 'digits: 4'])
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
                TextColumn::make('qualificationTitle.trainingProgram.title')
                    ->label('Qualification Title/Lot')
                    ->searchable(),
                TextColumn::make('price_per_toolkit')
                    ->label('Price per Toolkit')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make('number_of_toolkit')
                    ->label('No. of Toolkits'),    
                TextColumn::make('total_abc_per_lot')
                    ->label('Total ABC per Lot')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),  
                TextColumn::make('number_of_items_per_toolkit')
                    ->label('No. of Items per Toolkit'),  
                TextColumn::make('year')
                    ->label('Year'), 
            ])
            ->filters([
                //
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
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('qualificationTitle.trainingProgram.name')
                                        ->heading('Qualification Title / Lot')
                                        ->getStateUsing(function ($record) {
                                            $qualificationTitle = $record->qualificationTitle;
                                            return $qualificationTitle ? $qualificationTitle->trainingProgram->title : 'No province available';
                                        }),

                                    Column::make('price_per_toolkit')
                                        ->heading('Estimated Price Per Toolkit')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),

                                    Column::make('number_of_toolkit')
                                        ->heading('Number of Toolkits Per Lot'),

                                    Column::make('total_abc_per_lot')
                                        ->heading('Total ABC Per Lot')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),

                                    Column::make('number_of_items_per_toolkit')
                                        ->heading('Number of Items Per Toolkit'),

                                    Column::make('year')
                                        ->heading('Year'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Skill Priorities')
                        ])
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
            'index' => Pages\ListToolkits::route('/'),
            'create' => Pages\CreateToolkit::route('/create'),
            'edit' => Pages\EditToolkit::route('/{record}/edit'),
        ];
    }
}
