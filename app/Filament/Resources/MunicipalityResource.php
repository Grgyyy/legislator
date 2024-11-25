<?php

namespace App\Filament\Resources;

use App\Models\Municipality;
use App\Models\District;
use App\Filament\Resources\MunicipalityResource\Pages;
use App\Services\NotificationHandler;
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
use App\Models\Province;

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationParentItem = "Regions";

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("code")
                    ->label('Code')
                    ->placeholder(placeholder: 'Enter code name')
                    ->autocomplete(false),

                TextInput::make("name")
                    ->label('Municipality')
                    ->placeholder(placeholder: 'Enter municipality name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Municipality'),

                TextInput::make("class")
                    ->label('Municipality Class')
                    ->placeholder(placeholder: 'Enter municipality class')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false),
                
                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No province available'];
                    })
                    ->reactive() // Make the field reactive to trigger updates in dependent fields
                    ->afterStateUpdated(function (callable $set) {
                        $set('district_id', null); // Clear the district_id field when province_id is cleared or updated
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province'),
                
                Select::make('district_id')
                    ->label('District')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function (callable $get) {
                        $selectedProvince = $get('province_id'); // Get the selected province ID
                        if (!$selectedProvince) {
                            return ['no_district' => 'No district available']; // Return a default option if no province is selected
                        }
                
                        return District::where('province_id', $selectedProvince)
                            ->whereNot('name', 'Not Applicable')
                            ->with('province.region', 'municipality') // Eager-load both province and municipality relationships
                            ->get()
                            ->mapWithKeys(function ($district) {
                                // Check if the province's region name is "NCR"
                                $isNCR = optional($district->province->region)->name === 'NCR';
                
                                // If NCR, show municipality name, else show province name
                                if ($isNCR) {
                                    // If NCR, show district and municipality
                                    $label = $district->name . ' - ' . ($district->municipality->pluck('name')->implode(', ') ?? 'No Municipality');
                                } else {
                                    // If not NCR, show district and province
                                    $label = $district->name;
                                }
                
                                return [
                                    $district->id => $label,
                                ];
                            })
                            ->toArray() ?: ['no_district' => 'No district available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_district')
                
                
                
                                


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no municipalities available')
            ->columns([
                TextColumn::make("code")
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("name")
                    ->label("Municipality")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("class")
                    ->searchable()
                    ->toggleable(),


                TextColumn::make('district')
                    ->label('District/s')
                    ->getStateUsing(fn($record) => $record->district->pluck('name')->join(', '))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("province.name")
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("province.region.name")
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Municipality has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Municipality has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Municipality has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected municipalities have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected municipalities have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected municipalities have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')->heading('Municipality'),
                                    Column::make('province.name')->heading('Province'),
                                    Column::make('province.region.name')->heading('Region'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Municipality')
                        ]),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        $query->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNot('name', 'Not Applicable');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('district_id', (int) $routeParameter);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMunicipalities::route('/'),
            'create' => Pages\CreateMunicipality::route('/create'),
            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
            'showMunicipality' => Pages\ShowMunicipalities::route('/{record}/municipalities'),
        ];
    }
}
