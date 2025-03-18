<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomTargetRemarksExport;
use App\Filament\Resources\TargetRemarkResource\Pages;
use App\Models\TargetRemark;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class TargetRemarkResource extends Resource
{
    protected static ?string $model = TargetRemark::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "MANAGE TARGET";
    protected static ?string $navigationLabel = "Target Remarks";

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('remarks')
                    ->required()
                    ->markAsRequired(false)
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('remarks')
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter abdd sectors')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete target remarks ')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomTargetRemarksExport::make()
                                ->withColumns([
                                    Column::make('remarks')
                                        ->heading('Remarks')

                                ])
                                ->withFilename(now()->format('m-d-Y') . ' - Target Remarks'),
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
            'index' => Pages\ListTargetRemarks::route('/'),
            'create' => Pages\CreateTargetRemark::route('/create'),
            'edit' => Pages\EditTargetRemark::route('/{record}/edit'),
        ];
    }

}
