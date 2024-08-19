<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use App\Models\InstitutionClass;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\InstitutionClassResource\Pages;

class InstitutionClassResource extends Resource
{
    protected static ?string $model = InstitutionClass::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";
    protected static ?string $navigationParentItem = "Institution";
    protected static ?string $navigationLabel = "Institution Class (B)";
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->autocomplete(false)
                    ->label('Institution Class (B)')
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No institution class yet')
            ->columns([
                TextColumn::make('name')
                    ->label('Institution Classes (B)')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])
            ->filtersTriggerAction(
                fn(\Filament\Actions\StaticAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
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
                                Column::make('name')
                                    ->heading('Institution Class (B) Name'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Institution Class (B)')
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
            'index' => Pages\ListInstitutionClasses::route('/'),
            'create' => Pages\CreateInstitutionClass::route('/create'),
            'edit' => Pages\EditInstitutionClass::route('/{record}/edit'),
        ];
    }
}
