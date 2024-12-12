<?php

namespace App\Filament\Clusters\Sectors\Resources;

use Filament\Forms;
use App\Models\Abdd;
use Filament\Tables;
use App\Models\Province;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProvinceAbdd;
use Filament\Resources\Resource;
use App\Filament\Clusters\Sectors;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\Pages;
use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\RelationManagers;
use App\Services\NotificationHandler;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

class ProvinceAbddResource extends Resource
{
    protected static ?string $model = ProvinceAbdd::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Sectors::class;
    protected static ?string $navigationLabel = "Province ABDD Sectors Slots";

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Completely remove from side navigation
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('province_id')
                    ->required()
                    ->markAsRequired(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No province available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province')
                    ->searchable(),

                Select::make('abdd_id')
                    ->required()
                    ->markAsRequired(false)
                    ->options(function () {
                        return Abdd::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_abdd' => 'No ABDD Sectors available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_abdd')
                    ->searchable(),

                TextInput::make('available_slots')
                    ->required()
                    ->markAsRequired(false)
                    ->numeric()
                    ->hidden(fn($livewire) => !$livewire->isEdit()), // Use the public method

                TextInput::make('total_slots')
                    ->required()
                    ->markAsRequired(false)
                    ->numeric(),

                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->default(date('Y'))
                    ->rules(['min:' . date('Y'), 'digits:4'])
                    ->validationAttribute('year')
                    ->validationMessages([
                        'min' => 'The allocation year must be at least ' . date('Y') . '.',
                    ]),
            ]);
    }






    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provinces.name')
                    ->label('Province Name'),
                TextColumn::make('abdds.name')
                    ->label('ABDD Name'),
                TextColumn::make('available_slots')
                    ->label('Available Slots'),
                TextColumn::make('total_slots')
                    ->label('Total Slots'),
                TextColumn::make('year')
                    ->label('Year'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('addSlots')
                ->modalContent(function (ProvinceAbdd $record): HtmlString {
                            return new HtmlString("
                                <div style='margin-bottom: 1rem; margin-top: 1rem; font-size: .9rem; display: grid; grid-template-columns: 1fr 2fr; gap: 10px;'>
                                    <div style='font-weight: bold;'>ABDD Sector:</div>
                                    <div>{$record->abdds->name}</div>    
                                
                                    <div style='font-weight: bold;'>Province:</div>
                                    <div>{$record->provinces->name}</div>

                                    <div style='font-weight: bold;'>Available Slots:</div>
                                    <div>{$record->available_slots}</div>

                                    <div style='font-weight: bold;'>Total Slots:</div>
                                    <div>{$record->total_slots}</div>

                                    <div style='font-weight: bold;'>Year:</div>
                                    <div>{$record->year}</div>
                                </div>
                                
                            ");
                        })
                        ->modalHeading('Add ABDD Slots')
                        ->modalWidth(MaxWidth::TwoExtraLarge)
                        ->icon('heroicon-o-plus')
                        ->label('Add Slots')
                        ->form([
                            TextInput::make('available_slots')
                                ->label('Add Slots')
                                ->autocomplete(false)
                                ->integer()
                                ->default(0)
                                // ->minValue(0)
                                // ->maxValue(function (Allocation $record) {
                                //     return 999999999999.99 - $record->allocation;
                                // })
                                // ->validationMessages([
                                //     'max' => 'The allocation cannot exceed ₱999,999,999,999.99.'
                                // ]),
                        ])
                        ->action(function (array $data, ProvinceAbdd $record): void {
                            $record->available_slots += $data['available_slots'];

                            $record->total_slots += $data['available_slots'];

                            $record->save();

                            NotificationHandler::sendSuccessNotification('Saved', 'ABDD slots have been added successfully.');
                        })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('provinces.name')
                                        ->heading('Province Name'),
                                    Column::make('abdds.name')
                                        ->heading('ABDD Name'),
                                    Column::make('available_slots')
                                        ->heading('total_slots'),
                                    Column::make('total_slots')
                                        ->heading('Total Slots'),
                                    Column::make('year')
                                        ->heading('Year'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Province ABDDs')
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
            // 'index' => Pages\ListProvinceAbdds::route('/'),
            // 'create' => Pages\CreateProvinceAbdd::route('/create'),
            // 'edit' => Pages\EditProvinceAbdd::route('/{record}/edit'),
        ];
    }
}
