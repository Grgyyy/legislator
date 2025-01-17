<?php

namespace App\Filament\Resources;

use App\Models\ScholarshipProgram;
use App\Filament\Resources\ScholarshipProgramResource\Pages;
use App\Services\NotificationHandler;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScholarshipProgramResource extends Resource
{
    protected static ?string $model = ScholarshipProgram::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label("Scholarship Program")
                    ->placeholder('Enter scholarship program')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Scholarship Program'),

                TextInput::make("code")
                    ->label('Scholarship Program Code')
                    ->placeholder('Enter scholarship program code')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Scholarship Program Code'),

                TextInput::make("desc")
                    ->label('Description')
                    ->placeholder('Enter scholarship program description')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No scholarship programs available')
            ->columns([
                TextColumn::make("code")
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->url(fn($record) => route('filament.admin.resources.scholarship-programs.showTrainingPrograms', ['record' => $record->id])),

                TextColumn::make("name")
                    ->label("Scholarship Program")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("desc")
                    ->label("Description")
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Scholarship program has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Scholarship program has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Scholarship program has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected scholarship programs have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected scholarship programs have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected scholarship programs have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('code')
                                        ->heading('Scholarship Program Code'),
                                    Column::make('name')
                                        ->heading('Scholarship Program'),
                                    Column::make('desc')
                                        ->heading('Description'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Scholarship Program')
                        ]),

                ]),
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
            'index' => Pages\ListScholarshipPrograms::route('/'),
            'create' => Pages\CreateScholarshipProgram::route('/create'),
            'edit' => Pages\EditScholarshipProgram::route('/{record}/edit'),
            'showTrainingPrograms' => Pages\ShowTrainingPrograms::route('/{record}/trainingPrograms')
        ];
    }
}