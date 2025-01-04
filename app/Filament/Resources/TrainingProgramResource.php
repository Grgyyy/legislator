<?php

namespace App\Filament\Resources;

use App\Models\TrainingProgram;
use App\Models\ScholarshipProgram;
use App\Models\Tvet;
use App\Models\Priority;
use App\Filament\Resources\TrainingProgramResource\Pages;
use App\Services\NotificationHandler;
use Filament\Resources\Resource;
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
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label(label: 'Training Program Code')
                    ->placeholder('Enter training program code')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Training Program Code'),

                TextInput::make('title')
                    ->label(label: "Training Program")
                    ->placeholder('Enter training program')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Training Program'),

                Select::make('tvet_id')
                    ->label('TVET Sector')
                    ->relationship('tvet', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Tvet::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_tvet' => 'No TVET Sector Available'];
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
                        return Priority::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_priority' => 'No Priority Sector Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_priority'),

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
                            ->toArray() ?: ['no_scholarship_program' => 'No Scholarship Program Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no training programs available')
            ->columns([
                TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => ucwords($state)),

                TextColumn::make('priority.name')
                    ->label('Priority Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvet.name')
                    ->label('TVET Sector')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('scholarshipPrograms.name')
                    ->label('Scholarship Program')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $scholarshipPrograms = $record->scholarshipPrograms->pluck('name')->toArray();

                        $schoProHtml = array_map(function ($name, $index) use ($scholarshipPrograms) {
                            $comma = ($index < count($scholarshipPrograms) - 1) ? ', ' : '';

                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';

                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                        }, $scholarshipPrograms, array_keys($scholarshipPrograms));

                        return implode('', $schoProHtml);
                    })
                    ->html(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),

                SelectFilter::make('scholarshipPrograms')
                    ->label('Scholarship Program')
                    ->relationship('scholarshipPrograms', 'name'),
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
                                    Column::make('code')
                                        ->heading('Qualification Code'),
                                    Column::make('title')
                                        ->heading('Qualification Title'),
                                    Column::make('priority.name')
                                        ->heading('Priority Sector'),
                                    Column::make('tvet.name')
                                        ->heading('TVET Sector'),
                                    Column::make('formatted_scholarship_programs')
                                        ->heading('Scholarship Programs')
                                        ->getStateUsing(fn($record) => $record->scholarshipPrograms
                                            ->pluck('name')
                                            ->implode(', ')
                                        ),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Training Programs')
                        ]),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('id');

        $query->withoutGlobalScopes([SoftDeletingScope::class]);

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->whereHas('scholarshipPrograms', function (Builder $query) use ($routeParameter) {
                $query->where('scholarship_programs.id', $routeParameter);
            });
        }

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
