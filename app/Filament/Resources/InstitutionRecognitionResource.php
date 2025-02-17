<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomInstitutionRecognitionExport;
use App\Filament\Resources\InstitutionRecognitionResource\Pages;
use App\Models\InstitutionRecognition;
use App\Models\Recognition;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Carbon\Carbon;
use Date;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                    ->closeOnDateSelection(),
                // ->minDate(today()),
                // ->rules(['after_or_equal:today']),

                DatePicker::make('expiration_date')
                    ->label('Expiration Date')
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->weekStartsOnSunday()
                    ->closeOnDateSelection()
                    ->minDate(fn($get) => $get('accreditation_date')
                        ? Carbon::parse($get('accreditation_date'))->addDay()->greaterThan(today())
                        ? Carbon::parse($get('accreditation_date'))->addDay()
                        : today()->addDay()
                        : today()->addDay())
                    ->rules(['after:today', 'after:accreditation_date']),





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
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter institution recognition')),
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
                                ->withFilename(date('m-d-Y') . ' - institution_recognition_export')
                        ])
                ])
                ->label('Select Action'),
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
