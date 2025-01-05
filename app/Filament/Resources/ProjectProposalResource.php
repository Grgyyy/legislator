<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectProposalResource\Pages;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Filament\Forms;
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
    protected static ?string $model = QualificationTitle::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Scholarship Programs";

    protected static ?string $navigationLabel = "Project Proposal Programs";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('program_name')
                    ->label('Project Proposal Program')
                    ->required()
                    ->markAsRequired(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trainingProgram.title')
                    ->label('Program Name')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return $state;
                        }

                        $state = ucwords($state);

                        if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                            $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                return 'NC ' . strtoupper($matches[1]);
                            }, $state);
                        }

                        return $state;
                    })
                    ->sortable(),

                SelectColumn::make('status_id')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '2' => 'Inactive',
                    ])
                    ->disablePlaceholderSelection()
                    ->extraAttributes(['style' => 'width: 125px;'])

                // TextColumn::make('scholarshipProgram.name')
                //     ->label('Scholarship Programs')
                //     ->formatStateUsing(function ($state, $record) {
                //         return $record->scholarshipProgram->pluck('name')->implode(', ');
                //     }),
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
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('trainingProgram.title')
                                        ->heading('Qualification Code'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Qualification Titles')
                        ]),
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
            ->with(['trainingProgram', 'scholarshipProgram'])
            ->where('qualification_titles.soc', 0)
            ->select('qualification_titles.*')
            ->groupBy('qualification_titles.training_program_id');
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
