<?php

namespace App\Filament\Clusters\Sectors\Resources;

use App\Models\Abdd;
use App\Models\Province;
use App\Filament\Clusters\Sectors;
use App\Filament\Clusters\Sectors\Resources\AbddResource\Pages;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AbddResource extends Resource
{
    protected static ?string $model = Abdd::class;

    protected static ?string $cluster = Sectors::class;

    protected static ?string $navigationLabel = "ABDD Sectors";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Sector')
                    ->placeholder('Enter sector name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Sector'),

                // Select::make('province')
                //     ->label('Province')
                //     ->relationship('provinces', 'name')
                //     ->required()
                //     ->markAsRequired(false)
                //     ->searchable()
                //     ->preload()
                //     ->multiple()
                //     ->native(false)
                //     ->options(function () {
                //         return Province::whereNot('name', 'Not Applicable')
                //             ->pluck('name', 'id')
                //             ->toArray() ?: ['no_province' => 'No Province Available'];
                //     })
                //     ->disableOptionWhen(fn($value) => $value === 'no_province'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no sectors available')
            ->columns([
                TextColumn::make('name')
                    ->label("Sector")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                // TextColumn::make('provinces.name')
                //     ->label('Provinces')
                //     ->sortable()
                //     ->searchable()
                //     ->toggleable()
                //     ->formatStateUsing(function ($record) {
                //         $provinces = $record->provinces->pluck('name')->toArray();

                //         $provincesHtml = array_map(function ($name, $index) use ($provinces) {
                //             $comma = ($index < count($provinces) - 1) ? ', ' : '';

                //             $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';

                //             $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                //             return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                //         }, $provinces, array_keys($provinces));

                //         return implode('', $provincesHtml);
                //     })
                //     ->html(),
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
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('ABDD Sector'),
                                    // Column::make('provinces.name')
                                    //     ->heading('ABDD Sector')
                                    //     ->getStateUsing(fn($record) => $record->provinces->pluck('name')->implode(', ')),
                                ])
                                ->withFilename(date('m-d-Y') . ' - ABDD Sector')
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
            'index' => Pages\ListAbdds::route('/'),
            'create' => Pages\CreateAbdd::route('/create'),
            'edit' => Pages\EditAbdd::route('/{record}/edit'),
        ];
    }
}
