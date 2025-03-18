<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomProjectProposalProgramExport;
use App\Filament\Resources\ProjectProposalResource\Pages;
use App\Models\Priority;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Fieldset;
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
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->hidden(fn($livewire) => $livewire->noQualiCode())
                    ->validationAttribute('qualification code'),

                TextInput::make('soc_code')
                    ->label("Schedule of Cost Code")
                    ->placeholder('Enter soc code')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->hidden(fn($livewire) => $livewire->noSocCode())
                    ->disabled(fn($livewire) => $livewire->disabledSoc())
                    ->dehydrated()
                    ->validationAttribute('SoC code'),

                Select::make('full_coc_ele')
                    ->label('Qualification Type')
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->options([
                        'Full' => 'Full',
                        'COC' => 'COC',
                        'ELE' => 'ELE',
                        'NTR/CS' => 'NTR/CS',
                    ])
                    ->reactive()
                    ->live()
                    ->hidden(fn($livewire) => $livewire->noQualiCode())
                    ->validationAttribute('qualification type'),

                Select::make('nc_level')
                    ->label('NC Level')
                    ->required(fn($get) => $get('full_coc_ele') === 'Full')
                    ->markAsRequired(false)
                    ->native(false)
                    ->options([
                        'NC I' => 'NC I',
                        'NC II' => 'NC II',
                        'NC III' => 'NC III',
                        'NC IV' => 'NC IV',
                        'NC V' => 'NC V',
                        'NC VI' => 'NC VI',
                    ])
                    ->reactive()
                    ->live()
                    ->hidden(fn($get) => $get('full_coc_ele') !== 'Full')
                    ->validationAttribute('NC level'),

                TextInput::make('title')
                    ->label("Qualification Title")
                    ->placeholder('Enter qualification title')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('qualification title'),

                // Select::make('scholarshipPrograms')
                //     ->label('Scholarship Program')
                //     ->required()
                //     ->markAsRequired(false)
                //     ->searchable()
                //     ->preload()
                //     ->multiple(fn($get) => request()->get('scholarship_program_id') === null)
                //     ->default(fn($get) => request()->get('scholarship_program_id'))
                //     ->native(false)
                //     ->options(function () {
                //         return ScholarshipProgram::all()
                //             ->pluck('name', 'id')
                //             ->toArray() ?: ['no_scholarship_program' => 'No scholarship programs available'];
                //     })
                //     ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                //     ->validationAttribute('scholarship program'),

                // Select::make('tvet_id')
                //     ->label('TVET Sector')
                //     ->relationship('tvet', 'name')
                //     ->required()
                //     ->markAsRequired(false)
                //     ->searchable()
                //     ->preload()
                //     ->native(false)
                //     ->options(function () {
                //         return Tvet::whereNot('name', 'Not Applicable')
                //             ->pluck('name', 'id')
                //             ->toArray() ?: ['no_tvet' => 'No TVET sectors available'];
                //     })
                //     ->disableOptionWhen(fn($value) => $value === 'no_tvet')
                //     ->validationAttribute('TVET sector'),

                // Select::make('priority_id')
                //     ->label('Priority Sector')
                //     ->relationship('priority', 'name')
                //     ->required()
                //     ->markAsRequired(false)
                //     ->searchable()
                //     ->preload()
                //     ->native(false)
                //     ->options(function () {
                //         return Priority::whereNot('name', 'Not Applicable')
                //             ->pluck('name', 'id')
                //             ->toArray() ?: ['no_priority' => 'No priority sectors available'];
                //     })
                //     ->disableOptionWhen(fn($value) => $value === 'no_priority')
                //     ->validationAttribute('priority sector'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No project proposal programs available')
            ->paginated([5, 10, 25, 50])
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
                    ->tooltip(fn($state): ?string => strlen($state) > 50 ? $state : null),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter project proposal program')),

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
                                ])
                                ->columns(1),
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

                        if (!empty($data['full_coc_ele'])) {
                            $indicators[] = 'Qualification Type: ' . $data['full_coc_ele'];
                        }

                        if (!empty($data['nc_level'])) {
                            $indicators[] = 'NC Level: ' . $data['nc_level'];
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
                    // Action::make('Convert')
                    //     ->icon('heroicon-o-arrows-right-left')
                    //     ->url(fn($record) => route('filament.admin.resources.project-proposals.convert', ['record' => $record])),
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

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected project proposal programs have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete project proposal program')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected project proposal programs have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore project proposal program')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected project proposal programs have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete project proposal program')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomProjectProposalProgramExport::make()
                                ->withColumns([
                                    Column::make('soc_code')
                                        ->heading('Schedule of Cost Code'),
                                    Column::make('title')
                                        ->heading('Qualification Title'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Project Proposal Programs')
                        ]),
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
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
