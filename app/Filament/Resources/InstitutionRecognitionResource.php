<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomInstitutionRecognitionExport;
use App\Filament\Resources\InstitutionRecognitionResource\Pages;
use App\Models\InstitutionRecognition;
use App\Models\Recognition;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

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
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->default(fn($get) => request()->get('tvi_id'))
                    ->options(function () {
                        return TVI::whereNot('name', 'Not Applicable')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($tvi) {
                                $schoolId = $tvi->school_id;
                                $formattedName = $schoolId ? "{$schoolId} - {$tvi->name}" : $tvi->name;

                                return [$tvi->id => $formattedName];
                            })
                            ->toArray() ?: ['no_tvi' => 'No institutions available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_tvi')
                    ->validationAttribute('institution'),

                Select::make('recognition_id')
                    ->label('Recognition Title')
                    ->required()
                    ->markAsRequired(false)
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->options(function () {
                        return Recognition::all()
                            ->sortBy('name')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_recognition' => 'No recognition titles available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_recognition')
                    ->validationAttribute('recognition title'),

                DatePicker::make('accreditation_date')
                    ->label('Accreditation Date')
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->weekStartsOnSunday()
                    ->closeOnDateSelection(),

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
            ->defaultSort('accreditation_date')
            ->emptyStateHeading('No institution recognitions available')
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('tvi.name')
                    ->label('Institution')
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('tvi', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('school_id', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        $schoolId = $record->tvi->school_id ?? '';
                        $institutionName = $record->tvi->name ?? '';

                        if ($schoolId) {
                            return "{$schoolId} - {$institutionName}";
                        }

                        return $institutionName;
                    })
                    ->limit(50)
                    ->tooltip(fn($state): ?string => strlen($state) > 50 ? $state : null),

                TextColumn::make('recognition.name')
                    ->label('Recognition Title')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('accreditation_date')
                    ->label('Accreditation Date')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('F j, Y')),

                TextColumn::make('expiration_date')
                    ->label('Expiration Date')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('F j, Y')),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter institution recognitions')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Recognition has been deleted successfully.');
                        }),

                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Recognition has been restored successfully.');
                        }),

                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Recognition has been deleted permanently.');
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
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete institution recognitions')),

                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected institution recognition have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore institution recognitions')),

                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected institution recognition have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete institution recognitions')),

                    ExportBulkAction::make()
                        ->exports([
                            CustomInstitutionRecognitionExport::make()
                                ->withColumns([
                                    Column::make('school_id')
                                        ->heading('School ID')
                                        ->getStateUsing(fn($record) => $record->school_id ?? '-'),

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
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('tvi_id', (int) $routeParameter);
        }

        return $query;
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
}
