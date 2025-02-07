<?php

namespace App\Filament\Resources;

use Date;
use Carbon\Carbon;
use App\Models\Tvi;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Recognition;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use App\Models\InstitutionRecognition;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Exports\CustomExport\CustomInstitutionRecognitionExport;
use App\Filament\Resources\InstitutionRecognitionResource\Pages;
use App\Filament\Resources\InstitutionRecognitionResource\RelationManagers;

class InstitutionRecognitionResource extends Resource
{
    protected static ?string $model = InstitutionRecognition::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Institution Recognitions';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Institutions";

    protected static ?int $navigationSort = 6;


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

                DatePicker::make('accreditation_date')
                    ->label('Accreditation Date')
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->weekStartsOnSunday()
                    ->closeOnDateSelection()
                    ->minDate(today())
                    ->rules(['after_or_equal:today']),

                DatePicker::make('expiration_date')
                    ->label('Expiration Date')
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->weekStartsOnSunday()
                    ->closeOnDateSelection()
                    ->minDate(fn($get) => $get('accreditation_date') ? Carbon::parse($get('accreditation_date'))->addDay() : today()->addDay())
                    ->rules(['after:accreditation_date']),



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
                TextColumn::make('accreditation_date')
                    ->label('Accreditation Date')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('F j, Y')),

                TextColumn::make('expiration_date')
                    ->label('Expiration Date')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('F j, Y')),

            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Allocation has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Allocation has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Allocation has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected institution recognition have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete institution recognition')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institution recognition have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore institution recognition')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institution recognition have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete institution recognition')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomInstitutionRecognitionExport::make()
                                ->withColumns([
                                    Column::make('tvi.name')
                                        ->heading('Institution'),
                                    Column::make('recognition.name')
                                        ->heading('Recognition Title'),
                                    Column::make('accreditation_date')
                                        ->heading('Accreditation Date')
                                        ->formatStateUsing(fn($state) => Carbon::parse($state)->format('F j, Y')),
                                    Column::make('expiration_date')
                                        ->heading('Expiration Date')
                                        ->formatStateUsing(fn($state) => Carbon::parse($state)->format('F j, Y')),
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

        $query->orderBy('accreditation_date', 'desc');

        return $query;
    }
}
