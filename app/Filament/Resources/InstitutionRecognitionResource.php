<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use App\Models\Tvi;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Recognition;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use App\Models\InstitutionRecognition;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\InstitutionRecognitionResource\Pages;
use App\Filament\Resources\InstitutionRecognitionResource\RelationManagers;

class InstitutionRecognitionResource extends Resource
{
    protected static ?string $model = InstitutionRecognition::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Institution Recognitions';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tvi_id')
                    ->label('Institution')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->default(fn($get) => request()->get('tvi_id'))
                    ->options(function () {
                        return Tvi::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_tvi' => 'No institution available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_tvi'),

                Select::make('recognition_id')
                    ->label('Recognition Title')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Recognition::all()
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_recognition' => 'No recognition available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_recognition'),

                Select::make('year')
                    ->label('Year')
                    ->required()
                    ->markAsRequired(false)
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = range($currentYear - 2, $currentYear);
                        return array_combine($years, $years);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('recognition.name')
                    ->label('Recognition Title')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('year')
                    ->label('Year')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('tvi.name')
                                        ->heading('Institution'),
                                    Column::make('recognition.name')
                                        ->heading('Recognition Title'),
                                    Column::make('year')
                                        ->heading('Year'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Institution Recognitions')
                        ])
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
            'index' => Pages\ListInstitutionRecognitions::route('/'),
            'create' => Pages\CreateInstitutionRecognition::route('/create'),
            'edit' => Pages\EditInstitutionRecognition::route('/{record}/edit'),
            'showRecognition' => Pages\ShowInstitutionRecognition::route('/{record}/recognitions')
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('tvi_id', (int) $routeParameter);
        }

        $query->orderBy('year', 'desc');

        return $query;
    }
}
