<?php

namespace App\Filament\Resources;

use App\Models\Municipality;
use App\Models\District;
use App\Models\Province;
use App\Filament\Resources\MunicipalityResource\Pages;
use App\Services\NotificationHandler;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static ?string $navigationGroup = 'TARGET DATA INPUT';

    protected static ?string $navigationParentItem = 'Regions';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('Code')
                    ->placeholder('Enter code name')
                    ->autocomplete(false),

                TextInput::make('name')
                    ->label('Municipality')
                    ->placeholder('Enter municipality name')
                    ->required()
                    ->autocomplete(false)
                    ->validationAttribute('Municipality'),

                TextInput::make('class')
                    ->label('Municipality Class')
                    ->placeholder('Enter municipality class')
                    ->required()
                    ->autocomplete(false),

                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(fn() => Province::whereNot('name', 'Not Applicable')->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(fn(callable $set) => $set('district_id', [])),

                    MultiSelect::make('district_id')
                    ->label('District')
                    ->relationship('district', 'name') // Use the `district()` relationship
                    ->preload()
                    ->searchable()
                    ->required(false)
                    ->options(function (callable $get) {
                        $selectedProvince = $get('province_id'); // Get the selected province
                        
                        if (!$selectedProvince) {
                            return ['no_district' => 'No district available']; // Default if no province is selected
                        }
                
                        // Retrieve districts with their first associated municipality name
                        return District::with('municipality')
                            ->where('province_id', $selectedProvince)
                            ->where('name', '!=', 'Not Applicable')
                            ->get()
                            ->mapWithKeys(function ($district) {
                                $municipalityName = $district->underMunicipality->name ?? null; 
                                return [
                                    $district->id => $municipalityName 
                                        ? "{$district->name} - {$municipalityName}" 
                                        : "{$district->name}"
                                ];
                            })                            
                            ->toArray();
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_district')
                            
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->toggleable(),
                TextColumn::make('name')->label('Municipality')->sortable()->searchable()->toggleable(),
                TextColumn::make('class')->searchable()->toggleable(),
                TextColumn::make('district')
                    ->label('District/s')
                    ->getStateUsing(function ($record) {
                        return $record->district->map(function ($district) {
                            $municipalityName = $district->underMunicipality->name ?? null;
                    
                            return $municipalityName 
                                ? "{$district->name} - {$municipalityName}" 
                                : "{$district->name}";
                        })->join(', ');
                    })                    
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('province.name')->searchable()->toggleable(),
                TextColumn::make('province.region.name')->searchable()->toggleable(),
            ])
            ->filters([TrashedFilter::make()->label('Records')])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()->action(function ($record) {
                        $record->delete();
                        NotificationHandler::sendSuccessNotification('Deleted', 'Municipality has been deleted successfully.');
                    }),
                    RestoreAction::make()->action(function ($record) {
                        $record->restore();
                        NotificationHandler::sendSuccessNotification('Restored', 'Municipality has been restored successfully.');
                    }),
                    ForceDeleteAction::make()->action(function ($record) {
                        $record->forceDelete();
                        NotificationHandler::sendSuccessNotification('Force Deleted', 'Municipality has been permanently deleted.');
                    }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->action(fn($records) => $records->each->delete()),
                    RestoreBulkAction::make()->action(fn($records) => $records->each->restore()),
                    ForceDeleteBulkAction::make()->action(fn($records) => $records->each->forceDelete()),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->withColumns([
                                Column::make('name')->heading('Municipality'),
                                Column::make('province.name')->heading('Province'),
                                Column::make('province.region.name')->heading('Region'),
                            ])
                            ->withFilename(now()->format('m-d-Y') . ' - Municipality'),
                    ]),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNot('name', 'Not Applicable');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMunicipalities::route('/'),
            'create' => Pages\CreateMunicipality::route('/create'),
            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
            'show' => Pages\ShowMunicipalities::route('/{record}'),
        ];
    }
}
