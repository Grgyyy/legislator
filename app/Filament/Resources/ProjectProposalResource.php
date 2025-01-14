<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectProposalResource\Pages;
use App\Models\Priority;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
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
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ProjectProposalResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?string $navigationLabel = "Project Proposal Programs";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make('code')
                    ->label("Qualification Code")
                    ->placeholder('Enter qualification code')
                    ->required()
                    ->hidden(fn($livewire) => $livewire->noQualiCode())
                    ->markAsRequired(false),

                TextInput::make('soc_code')
                    ->label("Schedule of Cost Code")
                    ->placeholder('Enter SOC code')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->hidden(fn($livewire) => $livewire->noSocCode())
                    ->disabled(fn($livewire) => $livewire->disabledSoc())
                    ->dehydrated()
                    ->validationAttribute('Schedule of Cost Code'),

                TextInput::make('title')
                    ->label("Qualification Title")
                    ->placeholder('Enter qualification title')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Qualification Title'),
                
                Select::make('scholarshipPrograms')
                    ->label('Scholarship Program')
                    ->relationship('scholarshipPrograms', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->multiple(fn($get) => request()->get('scholarship_program_id') === null)
                    ->default(fn($get) => request()->get('scholarship_program_id'))
                    ->native(false)
                    ->options(function () {
                        return ScholarshipProgram::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program'),

                Select::make('tvet_id')
                    ->label('TVET Sector')
                    ->relationship('tvet', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Tvet::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_tvet' => 'No TVET sectors available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_tvet'),

                Select::make('priority_id')
                    ->label('Priority Sector')
                    ->relationship('priority', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Priority::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_priority' => 'No priority sectors available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_priority'),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No project proposal programs available')
            ->columns([
                TextColumn::make('soc_code')
                    ->label('SOC Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Qualification Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->limit(50)
                    ->tooltip(fn ($state): ?string => strlen($state) > 50 ? $state : null),

                TextColumn::make('scholarshipPrograms.name')
                    ->label('Scholarship Program')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $scholarshipPrograms = $record->scholarshipPrograms->sortBy('name')->pluck('name')->toArray();

                        $schoProHtml = array_map(function ($name, $index) use ($scholarshipPrograms) {
                            $comma = ($index < count($scholarshipPrograms) - 1) ? ', ' : '';

                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';

                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                        }, $scholarshipPrograms, array_keys($scholarshipPrograms));

                        return implode('', $schoProHtml);
                    })
                    ->html(),

                TextColumn::make('tvet.name')
                    ->label('TVET Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->limit(35)
                    ->tooltip(fn ($state): ?string => strlen($state) > 35 ? $state : null),

                TextColumn::make('priority.name')
                    ->label('Priority Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->limit(35)
                    ->tooltip(fn ($state): ?string => strlen($state) > 35 ? $state : null),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
                
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
                                
                            Fieldset::make('Sectors')
                                ->schema([
                                    Select::make('tvet')
                                        ->label("TVET Sector")
                                        ->placeholder('All')
                                        ->relationship('tvet', 'name')
                                        ->options(function () {
                                            return Tvet::whereNot('name', 'Not Applicable')
                                                ->pluck('name', 'id')
                                                ->toArray() ?: ['no_tvet' => 'No TVET sectors available'];
                                        })
                                        ->disableOptionWhen(fn($value) => $value === 'no_tvet')
                                        ->reactive(),

                                    Select::make('priority')
                                        ->label("Priority Sector")
                                        ->placeholder('All')
                                        ->relationship('priority', 'name')
                                        ->options(function () {
                                            return Priority::whereNot('name', 'Not Applicable')
                                                ->pluck('name', 'id')
                                                ->toArray() ?: ['no_priority' => 'No priority sectors available'];
                                        })
                                        ->disableOptionWhen(fn($value) => $value === 'no_priority')
                                        ->reactive(),
                                ]),
                        ];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scholarship_program'] ?? null,
                                fn(Builder $query, $scholarshipProgramId) => $query->whereHas('scholarshipPrograms', function ($query) use ($scholarshipProgramId) {
                                    $query->where('scholarship_program_id', $scholarshipProgramId);
                                })
                            )

                            ->when(
                                $data['tvet'] ?? null,
                                fn(Builder $query, $tvetId) => $query->where('tvet_id', $tvetId)
                            )

                            ->when(
                                $data['priority'] ?? null,
                                fn(Builder $query, $priorityId) => $query->where('priority_id', $priorityId)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['scholarship_program'])) {
                            $indicators[] = 'Scholarship Program: ' . Optional(ScholarshipProgram::find($data['scholarship_program']))->name;
                        }

                        if (!empty($data['tvet'])) {
                            $indicators[] = 'TVET Sector: ' . Optional(Tvet::find($data['tvet']))->name;
                        }

                        if (!empty($data['priority'])) {
                            $indicators[] = 'Priority Sector: ' . Optional(Priority::find($data['priority']))->name;
                        }

                        return $indicators;
                    })
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    Action::make('Convert')
                        ->icon('heroicon-o-arrows-right-left')
                        ->action(function ($record, $data) {
                            // $record->soc = 1;
                            // $record->save();

                            // NotificationHandler::sendSuccessNotification(
                            //     'Conversion Successful',
                            //     'The Project Proposal Program has been successfully converted into a Qualification Title and is now ready for costing in the Schedule of Cost.'
                            // );

                            return redirect()->route('filament.admin.resources.project-proposals.convert', ['record' => $record]);
                        }),
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
                                    Column::make('soc_code')
                                        ->heading('Schedule of Cost Code'),
                                    Column::make('title')
                                        ->heading('Qualification Title'),
                                    Column::make('tvet.name')
                                        ->heading('TVET Sector'),
                                    Column::make('priority.name')
                                        ->heading('Priority Sector'),
                                    Column::make('formatted_scholarship_programs')
                                        ->heading('Scholarship Programs')
                                        ->getStateUsing(fn($record) => $record->scholarshipPrograms
                                            ->pluck('name')
                                            ->implode(', ')
                                        ),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Project Proposal Programs')
                        ]),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('soc', 0);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectProposals::route('/'),
            'create' => Pages\CreateProjectProposal::route('/create'),
            'edit' => Pages\EditProjectProposal::route('/{record}/edit'),
            'convert' => Pages\ConvertProjectProposal::route('/{record}/convert'),
        ];
    }
}