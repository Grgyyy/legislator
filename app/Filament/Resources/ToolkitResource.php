<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomToolkitExport;
use App\Filament\Resources\ToolkitResource\Pages;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\Toolkit;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                    ->multiple()
                    ->preload()
                    ->native()
                    ->options(function () {
                        $step_scholarship = ScholarshipProgram::where('name', 'STEP')->first();

                        if (!$step_scholarship) {
                            return ['no_toolkits' => 'No toolkits available'];
                        }

                        return QualificationTitle::whereNull('deleted_at')
                            ->where('scholarship_program_id', $step_scholarship->id)
                            ->get()
                            ->mapWithKeys(fn($title) => [$title->id => "{$title->trainingProgram->title}"])
                            ->toArray();
                    })
                    ->default(fn($record) => $record?->qualificationTitles?->pluck('id')->toArray() ?? [])
                    ->afterStateHydrated(
                        fn($set, $record) =>
                        $set('qualification_title_id', $record?->qualificationTitles?->pluck('id')->toArray() ?? [])
                    )
                    ->afterStateUpdated(function ($state, $set) {
                        if (!empty($state) && isset($state[0])) {
                            $qualificationTitle = QualificationTitle::find($state[0]);

                            if ($qualificationTitle) {
                                $set('lot_name', $qualificationTitle->trainingProgram->title ?? null);
                            } else {
                                $set('lot_name', null);
                            }
                        } else {
                            $set('lot_name', null);
                        }
                    })
                    ->reactive()
                    ->live()
                    ->validationAttribute('qualification title'),

                TextInput::make('lot_name')
                    ->label('Lot')
                    ->placeholder('Enter a lot name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->reactive()
                    ->live()
                    ->validationAttribute('lot name'),

                TextInput::make('number_of_toolkit')
                    ->label('Number of Toolkits')
                    ->placeholder('Enter number of toolkits')
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->minValue(1)
                    ->currencyMask(thousandSeparator: '', precision: 0)
                    // ->disabled(fn($livewire) => $livewire->isEdit())
                    ->afterStateHydrated(function ($set, ?Toolkit $record) {
                        $set('number_of_toolkit', $record?->number_of_toolkits ?? 0);
                    })
                    ->validationMessages([
                        'min' => 'The number of toolkit must be at least 1.',
                    ])
                    // ->dehydrated()
                    ->validationAttribute('number of toolkits'),

                TextInput::make('price_per_toolkit')
                    ->label('Price per Toolkit')
                    ->placeholder('Enter price per toolkits')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->prefix('₱')
                    ->default(0)
                    ->minValue(1)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->validationMessages([
                        'min' => 'The allocation must be at least ₱1.00',
                        'max' => 'The allocation cannot exceed ₱999,999,999,999.99.'
                    ])
                    ->validationAttribute('price per toolkit'),

                TextInput::make('number_of_items_per_toolkit')
                    ->label('Number of Items per Toolkit')
                    ->placeholder('Enter number of items per toolkit')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->minValue(1)
                    ->currencyMask(thousandSeparator: '', precision: 0)
                    ->validationMessages([
                        'min' => 'The number of toolkit must be at least 1.',
                    ])
                    ->validationAttribute('number of items per toolkit'),

                TextInput::make('year')
                    ->label('Year')
                    ->placeholder('Enter toolkit year')
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No toolkits available')
            ->columns([
                TextColumn::make('qualificationTitles')
                    ->label('SOC Title')
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('qualificationTitles.trainingProgram', function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%")
                                  ->orWhere('soc_code', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $qualificationTitles = $record->qualificationTitles->map(function ($qualificationTitle) {
                            $trainingProgram = $qualificationTitle->trainingProgram;

                            if ($trainingProgram) {
                                return "{$trainingProgram->soc_code} - {$trainingProgram->title}";
                            }

                            return null;
                        })
                            ->filter()
                            ->toArray();

                        if (empty($qualificationTitles)) {
                            return '-';
                        }

                        $schoProHtml = array_map(function ($title, $index) use ($qualificationTitles) {
                            $comma = ($index < count($qualificationTitles) - 1) ? ', ' : '';
                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';
                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$title}{$comma}{$lineBreak}</div>";
                        }, $qualificationTitles, array_keys($qualificationTitles));

                        return implode('', $schoProHtml);
                    })
                    ->html(),

                TextColumn::make('available_number_of_toolkits')
                    ->label('Available No. of Toolkits')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->available_number_of_toolkits ?? '-'),

                TextColumn::make('number_of_toolkits')
                    ->label('No. of Toolkits')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->number_of_toolkits ?? '-'),

                TextColumn::make('price_per_toolkit')
                    ->label('Price per Toolkit')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make('total_abc_per_lot')
                    ->label('Total ABC')
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->total_abc_per_lot
                        ? '₱' . number_format((float) $record->total_abc_per_lot, 2, '.', ',')
                        : '-'),

                TextColumn::make('number_of_items_per_toolkit')
                    ->label('No. of Items per Toolkit')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter toolkit')),

                Filter::make('year')
                    ->form([
                        TextInput::make('year')
                            ->label('Year')
                            ->placeholder('Enter year')
                            ->integer()
                            ->minLength(4)
                            ->maxLength(4)
                            ->currencyMask(thousandSeparator: '', precision: 0)
                            ->reactive()
                            ->live(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['year'] ?? null,
                                fn(Builder $query, $year) => $query->where('year', $year)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['year'])) {
                            $indicators[] = 'Year: ' . $data['year'];
                        }

                        return $indicators;
                    })
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
                            CustomToolkitExport::make()
                                ->withColumns([
                                    Column::make('qualificationTitles')
                                        ->heading('Qualification Titles')
                                        ->formatStateUsing(function ($record) {
                                            $qualificationTitles = $record->qualificationTitles->map(
                                                fn($qualificationTitle) =>
                                                optional($qualificationTitle->trainingProgram)->soc_code .
                                                ' - ' .
                                                optional($qualificationTitle->trainingProgram)->title
                                            )->filter()->toArray();

                                            return empty($qualificationTitles) ? '-' : implode(', ', $qualificationTitles);
                                        }),

                                    // Column::make('lot_name')
                                    //     ->heading('Lot Name'),

                                    Column::make('available_number_of_toolkits')
                                        ->heading('Available No. of Toolkits per Lot')
                                        ->getStateUsing(fn($record) => $record->available_number_of_toolkits ?? '-'),

                                    Column::make('number_of_toolkit')
                                        ->heading('No. of Toolkits')
                                        ->getStateUsing(fn($record) => $record->number_of_toolkits ?? '-'),

                                    Column::make('price_per_toolkit')
                                        ->heading('Price per Toolkit')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('total_abc_per_lot')
                                        ->heading('Total ABC')
                                        ->getStateUsing(fn($record) => $record->total_abc_per_lot
                                            ? '₱' . number_format((float) $record->total_abc_per_lot, 2, '.', ',')
                                            : '-'),

                                    Column::make('number_of_items_per_toolkit')
                                        ->heading('No. of Items per Toolkit'),

                                    Column::make('year')
                                        ->heading('Year'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Toolkit Export')
                        ])

                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
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
