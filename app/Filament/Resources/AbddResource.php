<?php

namespace App\Filament\Resources;

use App\Models\Province;
use Filament\Forms;
use App\Models\Abdd;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\AbddResource\Pages;
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
use App\Filament\Resources\AbddResource\RelationManagers;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Illuminate\Support\Facades\Log;

class AbddResource extends Resource
{
    protected static ?string $model = Abdd::class;

    protected static ?string $navigationGroup = "SECTORS";

    protected static ?string $navigationLabel = "ABDD Sectors";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Sector')
                    ->required()
                    ->autocomplete(false)
                    ->markAsRequired(false)
                    ->validationAttribute('sector'),
                Select::make('province')
                    ->label('Province')
                    ->multiple()
                    ->relationship('provinces', 'name')
                    ->options(function () {
                        $provinces = Province::whereNot('name', 'Not Applicable')->pluck('name', 'id')->toArray();
                        return !empty($provinces) ? $provinces : ['no_scholarship_program' => 'No Scholarship Program Available'];
                    })
                    ->preload()
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->disableOptionWhen(fn($value) => $value === 'no_scholarship_program'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No sectors yet')
            ->columns([
                TextColumn::make('name')
                    ->label("Sector")
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provinces.name')
                    ->label('Provinces')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn($record) => $record->provinces->pluck('name')->implode(', ')),
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
                                Column::make('name')
                                    ->heading('ABDD Sector'),
                                Column::make('formatted_provinces')
                                    ->heading('ABDD Sector'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - ABDD Sector')
                    ]),
                ]),
            ]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbdds::route('/'),
            'create' => Pages\CreateAbdd::route('/create'),
            'edit' => Pages\EditAbdd::route('/{record}/edit'),
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
