<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\ScholarshipProgram;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ScholarshipProgramResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;

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
                    ->required()
                    ->autocomplete(false),
                TextInput::make("code")
                    ->label('Scholarship Program Code')
                    ->required()
                    ->autocomplete(false),
                TextInput::make("desc")
                    ->label('Description')
                    ->required()
                    ->autocomplete(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No scholarship programs yet')
            ->columns([
                TextColumn::make("code")
                    ->sortable()
                    ->searchable()
                    ->url(fn($record) => route('filament.admin.resources.scholarship-programs.showTrainingPrograms', ['record' => $record->id]))
                    ->toggleable(),
                TextColumn::make("name")
                    ->label("Scholarship Program")
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScholarshipPrograms::route('/'),
            'create' => Pages\CreateScholarshipProgram::route('/create'),
            'edit' => Pages\EditScholarshipProgram::route('/{record}/edit'),
            'showTrainingPrograms' => Pages\ShowTrainingPrograms::route('/{record}/trainingPrograms')
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
