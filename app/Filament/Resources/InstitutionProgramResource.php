<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionProgramResource\Pages;
use App\Filament\Resources\InstitutionProgramResource\RelationManagers;
use App\Models\InstitutionProgram;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstitutionProgramResource extends Resource
{
    protected static ?string $model = InstitutionProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?string $navigationLabel = "Institution's Training Programs";

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tvi_id')
                    ->label('Institution')
                    ->relationship('tvi', 'name')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->default(fn($get) => request()->get('tvi_id'))
                    ->options(function () {
                        return Tvi::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->mapWithKeys(function ($name, $id) {
                                // $name = preg_replace_callback(
                                //     '/(\d)([a-zA-Z])/',
                                //     fn($matches) => $matches[1] . strtoupper($matches[2]),
                                //     ucwords($name)
                                // );
                                return [$id => $name];
                            })
                            ->toArray() ?: ['no_tvi' => 'No institution available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_tvi'),

                Select::make('training_program_id')
                    ->label('Qualification TItle')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return TrainingProgram::all()
                            ->pluck('title', 'id')
                            ->mapWithKeys(function ($title, $id) {
                                // Assuming `soc_code` is a column in the TrainingProgram model
                                $program = TrainingProgram::find($id);
                    
                                return [$id => "{$program->soc_code} - {$program->title}"];
                            })
                            ->toArray() ?: ['no_training_program' => 'No Training Program Available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_training_program')
                    ->live(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->searchable(),
                    // ->formatStateUsing(fn ($state) => preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($state))),
                TextColumn::make('trainingProgram.title')
                    ->label('Qualification Title')
                    ->searchable()
                    // ->formatStateUsing(function ($state) {
                    //     if (!$state) {
                    //         return $state;
                    //     }

                    //     $state = ucwords($state);

                    //     if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                    //         $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                    //             return 'NC ' . strtoupper($matches[1]);
                    //         }, $state);
                    //     }

                    //     return $state;
                    // })
            ])
            ->filters([ /* filters here */ ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Institution has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Institution has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Institution has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected institutions have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institutions have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institutions have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('tvi_id')
                                        ->heading('Institution')
                                        ->getStateUsing(function ($record) {
                                            $tvi = $record->tvi;

                                            if (!$tvi) {
                                                return 'No Institution Information';
                                            }

                                            return preg_replace_callback('/(\d)([a-zA-Z])/',
                                                fn($matches) => $matches[1] . strtoupper($matches[2]),
                                                ucwords($tvi->name));
                                        }),
                                    Column::make('training_program_id')
                                        ->heading('Qualification Title')
                                        ->getStateUsing(function ($record) {
                                            $trainingProgram = $record->trainingProgram;

                                            if (!$trainingProgram) {
                                                return 'No Training Program Information';
                                            }

                                            $title = ucwords($trainingProgram->title);

                                            if (preg_match('/\bNC\s+[I]{1,3}\b/i', $title)) {
                                                $title = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                                    return 'NC ' . strtoupper($matches[1]);
                                                }, $title);
                                            }

                                            return $title;
                                        }),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institutions Training Programs')
                        ]),
                ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relationships here
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        $query->withoutGlobalScopes([SoftDeletingScope::class]);

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('tvi_id', (int) $routeParameter);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutionPrograms::route('/'),
            'create' => Pages\CreateInstitutionProgram::route('/create'),
            'edit' => Pages\EditInstitutionProgram::route('/{record}/edit'),
            'showPrograms' => Pages\ShowInstitutionProgram::route('/{record}/showPrograms')
        ];
    }
}
