<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomScheduleOfCostExport;
use App\Filament\Resources\QualificationTitleResource\Pages;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\Status;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Filament\Actions\ForceDeleteAction;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class QualificationTitleResource extends Resource
{
    protected static ?string $model = QualificationTitle::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?string $navigationLabel = "Schedule of Cost";

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'schedule-of-cost';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('training_program_id')
                    ->label('Qualification Title')
                    ->relationship('trainingProgram', 'title')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return TrainingProgram::where('soc', 1)
                            ->pluck('title', 'id')
                            ->mapWithKeys(function ($title, $id) {
                                $program = TrainingProgram::find($id);

                                return [$id => "{$program->soc_code} - {$program->title}"];
                            })
                            ->toArray() ?: ['no_qualification_title' => 'No qualification titles available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_qualification_title')
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('scholarship_program_id', null);

                        $scholarshipPrograms = self::getScholarshipProgramsOptions($state);

                        $set('scholarshipProgramsOptions', $scholarshipPrograms);

                        if (count($scholarshipPrograms) === 1) {
                            $set('scholarship_program_id', key($scholarshipPrograms));
                        }
                    })
                    ->live()
                    ->validationAttribute('qualification title'),

                Select::make('scholarship_program_id')
                    ->label('Scholarship Program')
                    ->relationship('scholarshipProgram', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function ($get) {
                        $trainingProgramId = $get('training_program_id');

                        return $trainingProgramId
                            ? self::getScholarshipProgramsOptions($trainingProgramId)
                            : ['no_scholarship_program' => 'No scholarship programs available. Select a training program first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                    ->reactive()
                    ->live()
                    ->validationAttribute('scholarship program'),

                Fieldset::make('Costing')
                    ->schema([
                        TextInput::make('training_cost_pcc')
                            ->label('Training Cost PCC')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('training_support_fund')
                            ->label('Training Support Fund')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('assessment_fee')
                            ->label('Assessment Fee')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->default(0)
                            ->prefix('₱')
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('entrepreneurship_fee')
                            ->label('Entrepreneurship Fee')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('new_normal_assistance')
                            ->label('New Normal Assistance')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('accident_insurance')
                            ->label('Accident Insurance')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('book_allowance')
                            ->label('Book Allowance')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('uniform_allowance')
                            ->label('Uniform Allowance')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),

                        TextInput::make('misc_fee')
                            ->label('Miscellaneous Fee')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                    ])
                    ->columns(3),

                Fieldset::make('Duration')
                    ->schema([
                        TextInput::make('days_duration')
                            ->label('Days')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->suffix('day(s)')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: '', precision: 0)
                            ->validationAttribute('training days'),

                        TextInput::make('hours_duration')
                            ->label('Hours')
                            ->required()
                            ->markAsRequired(false)
                            ->autocomplete(false)
                            ->numeric()
                            ->suffix('hrs')
                            ->default(0)
                            ->minValue(0)
                            ->currencyMask(thousandSeparator: '', precision: 0)
                            ->validationAttribute('training hours'),
                    ])
                    ->columns(2),

                Select::make('status_id')
                    ->label('Status')
                    ->relationship('status', 'desc')
                    ->required()
                    ->markAsRequired(false)
                    ->hidden(fn(Page $livewire) => $livewire instanceof CreateRecord)
                    ->default(1)
                    ->native(false)
                    ->options(function () {
                        return Status::all()
                            ->pluck('desc', 'id')
                            ->toArray() ?: ['no_status' => 'No status available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_status'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No schedule of cost available')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('trainingProgram.code')
                    ->label('Qualification Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => empty($record->trainingProgram->code) ? '-' : $record->trainingProgram->code),

                TextColumn::make('trainingProgram.soc_code')
                    ->label('SOC Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('trainingProgram.title')
                    ->label('Qualification Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->limit(50)
                    ->tooltip(fn($state): ?string => strlen($state) > 50 ? $state : null),

                TextColumn::make('scholarshipProgram.name')
                    ->label('Scholarship Program')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("training_cost_pcc")
                    ->label("Training Cost PCC")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("training_support_fund")
                    ->label("Training Support Fund")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("assessment_fee")
                    ->label("Assessment Fee")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("entrepreneurship_fee")
                    ->label("Entrepreneurship Fee")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("new_normal_assistance")
                    ->label("New Normal Assistance")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("accident_insurance")
                    ->label("Accidental Insurance")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("book_allowance")
                    ->label("Book Allowance")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("uniform_allowance")
                    ->label("Uniform Allowance")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("misc_fee")
                    ->label("Miscellaneous Fee")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make("pcc")
                    ->label("Total PCC (w/o Toolkits)")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    }),

                TextColumn::make('days_duration')
                    ->label('Training Days')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        $suffix = $state == 1 ? ' day' : ' days';

                        return $state . $suffix;
                    }),

                TextColumn::make('hours_duration')
                    ->label('Training Hours')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        $suffix = $state == 1 ? ' hr' : ' hrs';

                        return $state . $suffix;
                    }),

                SelectColumn::make('status_id')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '2' => 'Inactive',
                    ])
                    ->disablePlaceholderSelection()
                    ->extraAttributes(['style' => 'width: 125px;'])
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter schedule of cost')),

                Filter::make('filter')
                    ->form(function () {
                        return [
                            Select::make('scholarship_program')
                                ->label("Scholarship Program")
                                ->placeholder('All')
                                ->relationship('scholarshipPrograms', 'name')
                                ->options(function () {
                                    return ScholarshipProgram::all()
                                        ->pluck('name', 'id')
                                        ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                                })
                                ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                                ->reactive(),

                            Select::make('status_id')
                                ->label("Status")
                                ->placeholder('All')
                                ->relationship('status', 'desc')
                                ->reactive(),
                        ];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scholarship_program'] ?? null,
                                fn(Builder $query, $scholarshipProgramId) => $query->where('scholarship_program_id', $scholarshipProgramId)
                            )

                            ->when(
                                $data['status_id'] ?? null,
                                fn(Builder $query, $statusId) => $query->where('status_id', $statusId)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['scholarship_program'])) {
                            $indicators[] = 'Scholarship Program: ' . Optional(ScholarshipProgram::find($data['scholarship_program']))->name;
                        }

                        if (!empty($data['status_id'])) {
                            $indicators[] = 'Status: ' . Optional(Status::find($data['status_id']))->desc;
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

                            NotificationHandler::sendSuccessNotification('Deleted', 'Schedule of cost has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Schedule of cost has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Schedule of cost has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected schedule of cost have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete schedule of cost')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected schedule of cost have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore schedule of cost')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected schedule of cost have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete schedule of cost')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomScheduleOfCostExport::make()
                                ->withColumns([
                                    Column::make('TrainingProgram.code')
                                        ->heading('Qualification Code')
                                        ->getStateUsing(fn($record) => empty($record->trainingProgram->code) ? '-' : $record->trainingProgram->code),

                                    Column::make('TrainingProgram.soc_code')
                                        ->heading('SOC Code'),

                                    Column::make('TrainingProgram.title')
                                        ->heading('Qualification Title'),

                                    Column::make('ScholarshipProgram.name')
                                        ->heading('Scholarship Program'),

                                    Column::make('training_cost_pcc')
                                        ->heading('Training Cost PCC')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('training_support_fund')
                                        ->heading('Training Support Fund')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('assessment_fee')
                                        ->heading('Assessment Fee')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('entrepreneurship_fee')
                                        ->heading('Entrepreneurship Fee')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('new_normal_assistance')
                                        ->heading('New Normal Assistance')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('accident_insurance')
                                        ->heading('Accidental Insurance')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('book_allowance')
                                        ->heading('Book Allowance')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('uniform_allowance')
                                        ->heading('Uniform Allowance')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('misc_fee')
                                        ->heading('Miscellaneous Fee')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('toolkit.price_per_toolkit')
                                        ->heading('Cost of Toolkits PCC')
                                        ->getStateUsing(function ($record) {
                                            return $record->toolkit && $record->toolkit->price_per_toolkit !== null
                                                ? number_format($record->toolkit->price_per_toolkit, 2, '.', ',')
                                                : '0.00';
                                        }),
                                    Column::make('pcc')
                                        ->heading('Total PCC (w/o Toolkits)')
                                        ->format('"₱ "#,##0.00'),

                                    Column::make('days_duration')
                                        ->heading('Training Days')
                                        ->formatStateUsing(function ($state) {
                                            $suffix = $state == 1 ? ' day' : ' days';

                                            return $state . $suffix;
                                        }),

                                    Column::make('hours_duration')
                                        ->heading('Training Hours')
                                        ->formatStateUsing(function ($state) {
                                            $suffix = $state == 1 ? ' hr' : ' hrs';

                                            return $state . $suffix;
                                        }),

                                    Column::make('status.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Schedule of Cost')
                        ]),
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getScholarshipProgramsOptions($trainingProgramId): array
    {
        if (!$trainingProgramId) {
            return ['no_qualification_title' => 'No qualification titles available'];
        }

        return ScholarshipProgram::whereHas('trainingPrograms', function ($query) use ($trainingProgramId) {
            $query->where('training_programs.id', $trainingProgramId);
        })
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNot('soc', 0);

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualificationTitles::route('/'),
            'create' => Pages\CreateQualificationTitle::route('/create'),
            'edit' => Pages\EditQualificationTitle::route('/{record}/edit'),
        ];
    }
}
