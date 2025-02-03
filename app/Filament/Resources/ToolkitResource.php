<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Toolkit;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
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
use App\Filament\Resources\ToolkitResource\Pages;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ToolkitResource\RelationManagers;

class ToolkitResource extends Resource
{
    protected static ?string $model = Toolkit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('qualification_title_id')
                    ->label('Qualification Title')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->multiple()  // Allow multiple selections
                    ->preload()
                    ->native()
                    ->options(function () {
                        $step_scholarship = ScholarshipProgram::where('name', 'STEP')->first();

                        return QualificationTitle::whereNull('deleted_at')
                            ->where('scholarship_program_id', $step_scholarship->id)
                            ->get()
                            ->mapWithKeys(function ($title) {
                                return [
                                    $title->id => "{$title->trainingProgram->title}",
                                ];
                            })
                            ->toArray() ?: ['no_toolkits' => 'No Toolkits available'];
                    })
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state && isset($state[0])) {  // Check if there is at least one selected value
                            $qualificationTitle = QualificationTitle::find($state[0]);  // Get the first selected ID
            
                            if ($qualificationTitle) {
                                $set('lot_name', $qualificationTitle->trainingProgram->title);
                            } else {
                                $set('lot_name', null); // Clear the value if no qualification title is found
                            }
                        } else {
                            $set('lot_name', null); // Clear the value if no ID is selected
                        }
                    }),

                TextInput::make('lot_name')
                    ->label('Lot Name')
                    ->required()
                    ->markAsRequired(false)
                    ->placeholder('Enter a Lot Name'),


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
                    // ->required()
                    // ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    // ->default(0)
                    // ->minValue(0)
                    ->prefix('₱')
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
                        'min' => 'The toolkit year must be at least ' . date('Y') . '.',
                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('lot_name')
            ->columns([
                TextColumn::make('qualificationTitles')
                    ->label('Qualification Titles')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        // Ensure qualification titles are properly loaded and accessible
                        $qualificationTitles = $record->qualificationTitles->map(function ($qualificationTitle) {
                            $trainingProgram = $qualificationTitle->trainingProgram;
                            if ($trainingProgram) {
                                return "{$trainingProgram->soc_code} - {$trainingProgram->title}";
                            }
                            return null; // Return null if trainingProgram is not set
                        })->filter()->toArray();

                        // Check if qualification titles exist
                        if (empty($qualificationTitles)) {
                            return '-';  // Return a fallback message if no titles are found
                        }

                        // Format titles with commas, line breaks, and padding
                        $schoProHtml = array_map(function ($title, $index) use ($qualificationTitles) {
                            $comma = ($index < count($qualificationTitles) - 1) ? ', ' : '';
                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';
                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$title}{$comma}{$lineBreak}</div>";
                        }, $qualificationTitles, array_keys($qualificationTitles));

                        // Return the formatted HTML content
                        return implode('', $schoProHtml);
                    })
                    ->html(),

                TextColumn::make('lot_name')
                    ->label('Lot Name')
                    ->searchable(),
                TextColumn::make('price_per_toolkit')
                    ->label('Price per Toolkit')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make('available_number_of_toolkits')
                    ->label('Available Number of Toolkits Per Lot')
                    ->getStateUsing(fn($record) => $record->available_number_of_toolkits ?? '-'),

                TextColumn::make('number_of_toolkits')
                    ->label('No. of Toolkits')
                    ->getStateUsing(fn($record) => $record->number_of_toolkits ?? '-'),

                TextColumn::make('total_abc_per_lot')
                    ->label('Total ABC per Lot')
                    ->getStateUsing(fn($record) => $record->total_abc_per_lot
                        ? '₱' . number_format((float) $record->total_abc_per_lot, 2, '.', ',')
                        : '-'),

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
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected training programs have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete toolkit')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected training programs have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore toolkit')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected training programs have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete toolkit')),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('formatted_scholarship_programs')
                                        ->heading('Qualification Titles')
                                        ->getStateUsing(
                                            fn($record) => $record->qualificationTitles
                                                ->map(
                                                    fn($qualificationTitle) =>
                                                    $qualificationTitle->trainingProgram
                                                    ? "{$qualificationTitle->trainingProgram->soc_code}"
                                                    : null
                                                )
                                                ->filter() // Remove null values
                                                ->implode(', ')
                                        ),

                                    Column::make('lot_name')
                                        ->heading('Lot Name'),

                                    Column::make('price_per_toolkit')
                                        ->heading('Estimated Price Per Toolkit')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),

                                    Column::make('available_number_of_toolkits')
                                        ->heading('Available Number of Toolkits Per Lot')
                                        ->getStateUsing(fn($record) => $record->available_number_of_toolkits ?? '-'),

                                    Column::make('number_of_toolkit')
                                        ->heading('Number of Toolkits Per Lot')
                                        ->getStateUsing(fn($record) => $record->number_of_toolkits ?? '-'),

                                    Column::make('total_abc_per_lot')
                                        ->heading('Total ABC Per Lot')
                                        ->getStateUsing(fn($record) => $record->total_abc_per_lot
                                            ? '₱' . number_format((float) $record->total_abc_per_lot, 2, '.', ',')
                                            : '-'),

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
