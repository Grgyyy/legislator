<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomQualificationTitleExport;
use App\Filament\Resources\TrainingProgramResource\Pages;
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
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?string $navigationLabel = "Qualification Titles";

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'qualification-titles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Qualification Code')
                    ->placeholder('Enter qualification code')
                    ->autocomplete(false)
                    ->validationAttribute('qualification code'),

                TextInput::make('soc_code')
                    ->label('Schedule of Cost Code')
                    ->placeholder('Enter soc code')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
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
                    ->validationAttribute('qualification type'),

                Select::make('nc_level')
                    ->label('NC Level')
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
                    ->label('Qualification Title')
                    ->placeholder('Enter qualification title')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('qualification title'),

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
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program')
                    ->validationAttribute('scholarship program'),

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
                    ->disableOptionWhen(fn($value) => $value === 'no_tvet')
                    ->validationAttribute('TVET sector'),

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
                    ->disableOptionWhen(fn($value) => $value === 'no_priority')
                    ->validationAttribute('priority sector'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No qualification titles available')
            ->columns([
                TextColumn::make('code')
                    ->label('Qualification Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => empty($record->nc_level) ? '-' : $record->code),

                TextColumn::make('soc_code')
                    ->label('SOC Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('full_coc_ele')
                    ->label('Qualification Type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('nc_level')
                    ->label('NC Level')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => empty($record->nc_level) ? '-' : $record->nc_level),

                TextColumn::make('title')
                    ->label('Qualification Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->limit(50)
                    ->tooltip(fn($state): ?string => strlen($state) > 50 ? $state : null),

                TextColumn::make('scholarshipPrograms.name')
                    ->label('Scholarship Programs')
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
                    ->tooltip(fn($state): ?string => strlen($state) > 35 ? $state : null),

                TextColumn::make('priority.name')
                    ->label('Priority Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->limit(35)
                    ->tooltip(fn($state): ?string => strlen($state) > 35 ? $state : null),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter qualification title')),

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

                            Fieldset::make('')
                                ->schema([
                                    Select::make('full_coc_ele')
                                        ->label("Qualification Type")
                                        ->placeholder('All')
                                        ->options([
                                            'Full' => 'Full',
                                            'COC' => 'COC',
                                            'ELE' => 'ELE',
                                            'NTR/CS' => 'NTR/CS',
                                        ])
                                        ->reactive()
                                        ->live(),

                                    Select::make('nc_level')
                                        ->label("NC Level")
                                        ->placeholder('All')
                                        ->options([
                                            'NC I' => 'NC I',
                                            'NC II' => 'NC II',
                                            'NC III' => 'NC III',
                                            'NC IV' => 'NC IV',
                                            'NC V' => 'NC V',
                                            'NC VI' => 'NC VI',
                                        ])
                                        ->hidden(fn($get) => $get('full_coc_ele') !== 'Full')
                                        ->reactive()
                                        ->live(),
                                ])
                                ->columns(1),

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
                                $data['full_coc_ele'] ?? null,
                                fn(Builder $query, $fullCocEle) => $query->where('full_coc_ele', $fullCocEle)
                            )
                            ->when(
                                $data['nc_level'] ?? null,
                                fn(Builder $query, $ncLevel) => $query->where('nc_level', $ncLevel)
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

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected schedule of cost have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete qualification title')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected schedule of cost have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore qualification title')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected schedule of cost have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete qualification title')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomQualificationTitleExport::make()
                                ->withColumns([
                                    Column::make('code')
                                        ->heading('Qualification Code')
                                        ->getStateUsing(fn($record) => empty($record->nc_level) ? '-' : $record->code),
                                    Column::make('soc_code')
                                        ->heading('SOC Code'),
                                    Column::make('full_coc_ele')
                                        ->heading('Qualification Type'),
                                    Column::make('nc_level')
                                        ->heading('NC Level')
                                        ->getStateUsing(fn($record) => empty($record->nc_level) ? '-' : $record->nc_level),
                                    Column::make('title')
                                        ->heading('Qualification Title'),
                                    Column::make('scholarshipPrograms.name')
                                        ->heading('Scholarship Programs')
                                        ->getStateUsing(function ($record) {
                                            return $record->scholarshipPrograms->pluck('name')->implode(', ');
                                        }),
                                    Column::make('tvet.name')
                                        ->heading('TVET Sector'),
                                    Column::make('priority.name')
                                        ->heading('Priority Sector'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - qualification_title_export')
                        ])

                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('soc', 1);

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingPrograms::route('/'),
            'create' => Pages\CreateTrainingProgram::route('/create'),
            'edit' => Pages\EditTrainingProgram::route('/{record}/edit'),
        ];
    }
}
