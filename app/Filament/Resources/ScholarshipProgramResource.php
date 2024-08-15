<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\ScholarshipProgram;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use PhpOffice\PhpSpreadsheet\Calculation\LookupRef\Unique;
use App\Filament\Resources\ScholarshipProgramResource\Pages;
use App\Filament\Resources\ScholarshipProgramResource\RelationManagers;

class ScholarshipProgramResource extends Resource
{
    protected static ?string $model = ScholarshipProgram::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label("Scholarship Program")
                    ->required()
                    ->autocomplete(false)
                    ->unique(ignoreRecord: true), 
                TextInput::make("code")
                    ->label('Scholarship Program Code')
                    ->required()
                    ->autocomplete(false)
                    ->unique(ignoreRecord: true),
                TextInput::make("desc")
                    ->label('Description')
                    ->required()
                    ->autocomplete(false)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make("code")
                    ->label("Scholarship Program Code")
                    ->sortable()
                    ->searchable()
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn(\Filament\Actions\StaticAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->trashed()),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
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
                                    Column::make('created_at')
                                        ->heading('Date Created'),
                                ])->WithFilename(date('m-d-Y') . '- Scholarship Program'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScholarshipPrograms::route('/'),
            'create' => Pages\CreateScholarshipProgram::route('/create'),
            'edit' => Pages\EditScholarshipProgram::route('/{record}/edit'),
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
