<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\QualificationTitleResource\Pages;
use Filament\Resources\Pages\CreateRecord;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

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
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('scholarship_program_id', null);
                        $set('scholarshipProgramsOptions', self::getScholarshipProgramsOptions($state));
                    }),

                Select::make('scholarship_program_id')
                    ->label('Scholarship Programs')
                    ->options(fn($get) => self::getScholarshipProgramsOptions($get('training_program_id')))
                    ->required()
                    ->reactive()
                    ->searchable(),
                TextInput::make('training_cost_pcc')
                    ->label('Training Cost PCC')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('cost_of_toolkit_pcc')
                    ->label('Cost of Toolkit PCC')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('training_support_fund')
                    ->label('Training Support Fund')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('assessment_fee')
                    ->label('Assessment Fee')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('entrepeneurship_fee')
                    ->label('Entrepeneurship Fee')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('new_normal_assisstance')
                    ->label('New Normal Assisstance')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('accident_insurance')
                    ->label('Accident Insurance')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('book_allowance')
                    ->label('Book Allowance')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('duration')
                    ->label('Duration')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->suffix('hrs'),
                Select::make('status_id')
                    ->label('Status')
                    ->default(1)
                    ->relationship('status', 'desc')
                    ->hidden(fn(Page $livewire) => $livewire instanceof CreateRecord),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No qualification titles yet')
            ->columns([
                TextColumn::make('trainingProgram.code')
                    ->label('Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('trainingProgram.title')
                    ->label('Qualification Title')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('scholarshipProgram.name')
                    ->label('Scholarship Program')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("training_cost_pcc")
                    ->label("Training Cost PCC")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("cost_of_toolkit_pcc")
                    ->label("Cost of Toolkit PCC")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("training_support_fund")
                    ->label("Training Support Fund")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("assessment_fee")
                    ->label("Assessment Fee")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("entrepeneurship_fee")
                    ->label("Entrepeneurship Fee")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("new_normal_assisstance")
                    ->label("New Normal Assistance")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("accident_insurance")
                    ->label("Accidental Insurance")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("book_allowance")
                    ->label("Book Allowance")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("uniform_allowance")
                    ->label("Uniform Allowance")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make("misc_fee")
                    ->label("`Miscellaneous Fee`")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        return number_format($state, 2, '.', ',');
                    })
                    ->prefix('₱ '),
                TextColumn::make('hours_duration')
                    ->label('No. of Training Hours')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->suffix(' hrs'),
                TextColumn::make('days_duration')
                    ->label('No. of Training Days')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->suffix(' days'),
                TextColumn::make("status.desc")
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('status')
                    ->form([
                        Select::make('status_id')
                            ->label('Status')
                            ->options([
                                'all' => 'All',
                                '1' => 'Active',
                                '2' => 'Inactive',
                                'deleted' => 'Recently Deleted',
                            ])
                            ->default('all')
                            ->selectablePlaceholder(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['status_id'] === 'all',
                                fn(Builder $query): Builder => $query->whereNull('deleted_at')
                            )
                            ->when(
                                $data['status_id'] === 'deleted',
                                fn(Builder $query): Builder => $query->whereNotNull('deleted_at')
                            )
                            ->when(
                                $data['status_id'] === '1',
                                fn(Builder $query): Builder => $query->where('status_id', 1)->whereNull('deleted_at')
                            )
                            ->when(
                                $data['status_id'] === '2',
                                fn(Builder $query): Builder => $query->where('status_id', 2)->whereNull('deleted_at')
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('TrainingProgram.code')
                                    ->heading('Qualification Code'),
                                Column::make('TrainingProgram.title')
                                    ->heading('Qualification Title'),
                                Column::make('ScholarshipProgram.name')
                                    ->heading('Scholarship Program'),
                                Column::make('training_cost_pcc')
                                    ->heading('Training Cost PCC'),
                                Column::make('cost_of_toolkit_pcc')
                                    ->heading('Cost of Toolkit PCC'),
                                Column::make('training_support_fund')
                                    ->heading('Training Support Fund'),
                                Column::make('assessment_fee')
                                    ->heading('Assessment Fee'),
                                Column::make('entrepeneurship_fee')
                                    ->heading('Entrepreneurship fee'),
                                Column::make('new_normal_assisstance')
                                    ->heading('New normal assistance'),
                                Column::make('accident_insurance')
                                    ->heading('Accidental Insurance'),
                                Column::make('book_allowance')
                                    ->heading('Book Allowance'),
                                Column::make('uniform_allowance')
                                    ->heading('Uniform Allowance'),
                                Column::make('misc_fee')
                                    ->heading('Miscellaneous Fee'),
                                Column::make('duration')
                                    ->heading('Duration (Hrs)'),
                                Column::make('days_duration')
                                    ->heading('No. of Training Days'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Qualification Title')
                    ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualificationTitles::route('/'),
            'create' => Pages\CreateQualificationTitle::route('/create'),
            'edit' => Pages\EditQualificationTitle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Get available scholarship programs options based on selected training program.
     *
     * @param int|null $trainingProgramId
     * @return array
     */
    public static function getScholarshipProgramsOptions($trainingProgramId): array
    {
        if (!$trainingProgramId) {
            return [];
        }

        return ScholarshipProgram::whereHas('trainingPrograms', function ($query) use ($trainingProgramId) {
            $query->where('training_programs.id', $trainingProgramId);
        })
            ->pluck('name', 'id')
            ->toArray();
    }
}
