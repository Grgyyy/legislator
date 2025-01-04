<?php

namespace App\Filament\Resources;

use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Status;
use App\Filament\Resources\QualificationTitleResource\Pages;
use App\Services\NotificationHandler;
use Filament\Resources\Resource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QualificationTitleResource extends Resource
{
    protected static ?string $model = QualificationTitle::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                            ->mapWithKeys(fn ($title, $id) => [$id => ucwords($title)])
                            ->toArray() ?: ['no_training_program' => 'No Training Program Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_training_program')
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('scholarship_program_id', null);

                        $scholarshipPrograms = self::getScholarshipProgramsOptions($state);

                        $set('scholarshipProgramsOptions', $scholarshipPrograms);

                        if (count($scholarshipPrograms) === 1) {
                            $set('scholarship_program_id', key($scholarshipPrograms));
                        }
                    })
                    ->live(),

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
                            : ['no_scholarship_program' => 'No scholarship program available. Select a training program first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                    ->reactive()
                    ->live(),

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

                TextInput::make('cost_of_toolkit_pcc')
                    ->label('Cost of Toolkit PCC')
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

                TextInput::make('new_normal_assisstance')
                    ->label('New Normal Assisstance')
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

                TextInput::make('hours_duration')
                    ->label('Hours Duration')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->suffix('hrs')
                    ->default(0)
                    ->minValue(0),

                TextInput::make('days_duration')
                    ->label('Days Duration')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->suffix('day(s)')
                    ->default(0)
                    ->minValue(0),

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
                            ->toArray() ?: ['no_status' => 'No Status Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_status'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no qualification titles available')
            ->columns([
                TextColumn::make('trainingProgram.code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('trainingProgram.title')
                    ->label('Qualification Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => ucwords($state)),

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

                TextColumn::make("cost_of_toolkit_pcc")
                    ->label("Cost of Toolkit PCC")
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

                TextColumn::make("new_normal_assisstance")
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
                    ->label("Total PCC")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱ ')
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
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

                TextColumn::make('days_duration')
                    ->label('Training Days')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        $suffix = $state == 1 ? ' day' : ' days';

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
                    ->label('Records'),

                SelectFilter::make('training_program')
                    ->label('Training Program')
                    ->relationship('trainingProgram', 'title'),

                SelectFilter::make('scholarship_program')
                    ->label('Scholarship Program')
                    ->relationship('scholarshipProgram', 'name'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->relationship('status', 'desc'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Qualification title has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Qualification title has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Qualification title has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected qualifications titles have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected qualifications titles have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected qualifications titles have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('TrainingProgram.code')
                                        ->heading('Qualification Code'),
                                    Column::make('TrainingProgram.title')
                                        ->heading('Qualification Title'),
                                    Column::make('ScholarshipProgram.name')
                                        ->heading('Scholarship Program'),
                                    Column::make('training_cost_pcc')
                                        ->heading('Training Cost PCC')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('cost_of_toolkit_pcc')
                                        ->heading('Cost of Toolkit PCC')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('training_support_fund')
                                        ->heading('Training Support Fund')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('assessment_fee')
                                        ->heading('Assessment Fee')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('entrepreneurship_fee')
                                        ->heading('Entrepreneurship fee')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('new_normal_assisstance')
                                        ->heading('New normal assistance')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('accident_insurance')
                                        ->heading('Accidental Insurance')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('book_allowance')
                                        ->heading('Book Allowance')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('uniform_allowance')
                                        ->heading('Uniform Allowance')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('misc_fee')
                                        ->heading('Miscellaneous Fee')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('pcc')
                                        ->heading('Total PCC')
                                        ->formatStateUsing(fn($state) => '₱ ' . number_format($state, 2, '.', ',')),
                                    Column::make('hours_duration')
                                        ->heading('Duration (Hrs)'),
                                    Column::make('days_duration')
                                        ->heading('No. of Training Days'),
                                    Column::make('status.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Qualification Titles')
                        ]),
                ]),
            ]);
    }

    public static function getScholarshipProgramsOptions($trainingProgramId): array
    {
        if (!$trainingProgramId) {
            return ['no_training_program' => 'No Training Program Available'];
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
