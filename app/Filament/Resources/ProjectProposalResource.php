<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectProposalResource\Pages;
use App\Models\Priority;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                    // ->relationship('scholarshipPrograms', 'name')
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
            ->columns([

                TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

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
            ->filters([/* Any filters you want to apply */])
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
                    // ExportBulkAction::make()
                    //     ->exports([
                    //         ExcelExport::make()
                    //             ->withColumns([
                    //                 Column::make('trainingProgram.title')
                    //                     ->heading('Qualification Code')
                    //                     ->formatStateUsing(function ($state) {
                    //                         if (!$state) {
                    //                             return $state;
                    //                         }

                    //                         // Format all words to start with an uppercase letter
                    //                         $state = preg_replace_callback('/\b[a-z]+\b/i', function ($matches) {
                    //                             return ucfirst(strtolower($matches[0]));
                    //                         }, $state);

                    //                         // Handle 'NC I', 'NC II', etc., formatting
                    //                         $state = preg_replace_callback('/\bNC\s+[I]{1,3}\b/i', function ($matches) {
                    //                             return 'NC ' . strtoupper($matches[0]);
                    //                         }, $state);

                    //                         // Recursively format words inside parentheses at any position
                    //                         while (preg_match('/\(([^()]+)\)/', $state)) {
                    //                             $state = preg_replace_callback('/\(([^()]+)\)/', function ($matches) {
                    //                                 return '(' . preg_replace_callback('/\b[a-z]+\b/i', function ($wordMatches) {
                    //                                     return ucfirst(strtolower($wordMatches[0]));
                    //                                 }, $matches[1]) . ')';
                    //                             }, $state);
                    //                         }

                    //                         return $state;
                    //                     }),

                    //             ])
                    //             ->withFilename(date('m-d-Y') . ' - Qualification Titles')
                    //     ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['qualificationTitle'])
            ->whereHas('qualificationTitle', function ($query) {
                $query->where('soc', 0);
            })
            ;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectProposals::route('/'),
            'create' => Pages\CreateProjectProposal::route('/create'),
            'edit' => Pages\EditProjectProposal::route('/{record}/edit'),
        ];
    }
}
