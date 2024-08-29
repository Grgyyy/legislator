<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ExportBulkAction as ActionsExportBulkAction;
use Filament\Forms\Form;
use App\Models\Legislator;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\LegislatorResource\Pages;
use App\Models\Particular;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class LegislatorResource extends Resource
{
    protected static ?string $model = Legislator::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->label('Legislator')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->autocomplete(false),
                Select::make("particular")
                    ->multiple()
                    ->relationship("particular", "name")
                    ->required()
                    ->options(function () {
                        return Particular::query()
                            ->with('district')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                return [$item->id => $item->name . ' - ' . ($item->district ? $item->district->name : 'N/A') . ', ' . ($item->district->municipality ? $item->district->municipality->name : 'N/A')];
                            })
                            ->toArray();
                    }),
                Select::make('status_id')
                    ->label('Status')
                    ->default(1)
                    ->relationship('status', 'desc')
                    ->hidden(fn(Page $livewire) => $livewire instanceof CreateRecord),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No legislators yet')
            ->columns([
                TextColumn::make("name")
                    ->label('Legislator Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('particular_name')
                    ->label('Particular')
                    ->getStateUsing(function ($record) {
                        $particulars = $record->particular;

                        // return $particulars->map(function ($particular) {
                        //     $municipalityName =
                        //         $particular
                        //             ->district
                        //             ->name . ', ' .
                        //         $particular
                        //             ->district
                        //             ->municipality
                        //             ->name;
                        //     return $particular->name . ' - ' . $municipalityName;
                        // })->join(', ');
            

                        return $particulars->map(function ($particular) {
                            $districtName = $particular->district->name;
                            $municipalityName = $particular->district->municipality->name;
                            // $provinceName = $particular->district->municipality->province->name;
            
                            return $particular->name . ' - ' . $districtName . ' ' . $municipalityName;
                        })->join(', ');

                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("status.desc")
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
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
                                    ->heading('Legislator'),
                                Column::make('particular_name')
                                    ->heading('Particular'),
                            ])
                            ->withFilename(date('m-d-Y') . ' - Legislator')
                    ]),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegislators::route('/'),
            'create' => Pages\CreateLegislator::route('/create'),
            'edit' => Pages\EditLegislator::route('/{record}/edit'),
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
