<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use App\Services\NotificationHandler;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = "USER MANAGEMENT";

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->placeholder('Enter user full name')
                    ->required()
                    ->autocomplete(false)
                    ->validationAttribute('Name'),

                TextInput::make("email")
                    ->placeholder('Enter user email')
                    ->email()
                    ->required()
                    ->autocomplete(false)
                    ->validationAttribute('Email'),

                TextInput::make("password")
                    ->placeholder('Enter password')
                    ->password()
                    ->revealable()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->autocomplete(false)
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->regex('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$/')
                    ->minLength(8)
                    ->validationAttribute('Password'),

                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),

                Select::make('region_id')
                    ->label('Region')
                    ->relationship('region', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->reactive()
                    ->live()
                    ->multiple()
                    ->options(
                        Region::where('name', '!=', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray()
                    )


                    ->afterStateUpdated(function (Set $set, $get, $state) {
                        $selectedRegions = (array) $state;
                        $selectedProvinces = (array) $state;

                        $currentProvinces = (array) $get('province_id');
                        $currentDistricts = (array) $get('district_id');
                        $currentMunicipalities = (array) $get('municipality_id');

                        $filteredProvinces = Province::whereIn('id', $currentProvinces)
                            ->whereIn('region_id', $selectedRegions)
                            ->pluck('id')
                            ->toArray();

                        $filteredMunicipalities = Municipality::whereIn('id', $currentMunicipalities)
                            ->whereIn('province_id', $selectedProvinces)
                            ->pluck('id')
                            ->toArray();

                        $filteredDistricts = District::whereIn('id', $currentDistricts)
                            ->whereIn('province_id', $selectedProvinces)
                            ->pluck('id')
                            ->toArray();

                        $set('province_id', $filteredProvinces);
                        $set('municipality_id', $filteredMunicipalities);
                        $set('district_id', $filteredDistricts);
                    }),

                Select::make('province_id')
                    ->label('Province')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->reactive()
                    ->live()
                    ->multiple()
                    ->options(
                        fn($get) =>
                        !empty($get('region_id'))
                        ? Province::whereIn('region_id', (array) $get('region_id'))
                            ->where('name', '!=', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray()
                        : []
                    )

                    ->afterStateUpdated(function (Set $set, $get, $state) {
                        $selectedProvinces = (array) $state;
                        $currentMunicipalities = (array) $get('municipality_id');
                        $currentDistricts = (array) $get('district_id');

                        $filteredMunicipalities = Municipality::whereIn('id', $currentMunicipalities)
                            ->whereIn('province_id', $selectedProvinces)
                            ->pluck('id')
                            ->toArray();

                        $filteredDistricts = District::whereIn('id', $currentDistricts)
                            ->whereIn('province_id', $selectedProvinces)
                            ->pluck('id')
                            ->toArray();

                        $set('municipality_id', $filteredMunicipalities);
                        $set('district_id', $filteredDistricts);
                    }),

                Select::make('district_id')
                    ->label('District')
                    ->relationship('district', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->reactive()
                    ->live()
                    ->multiple()
                    ->options(
                        fn($get) => !empty($get('province_id'))
                        ? District::whereIn('province_id', (array) $get('province_id'))
                            ->orWhereHas('municipality', function ($query) use ($get) {
                                $query->whereIn('province_id', (array) $get('province_id'));
                            })
                            ->where('districts.name', '!=', 'Not Applicable')
                            ->distinct()
                            ->get()
                            ->mapWithKeys(function (District $district) {
                                $municipalityName = optional($district->underMunicipality)->name ?? '-';
                                $provinceName = optional($district->province)->name ?? '-';
                                $regionName = optional(optional($district->province)->region)->name ?? '-';

                                if ($district->underMunicipality) {
                                    return [
                                        $district->id => "{$district->name} - {$municipalityName}, {$provinceName}, {$regionName}"
                                    ];
                                } else {
                                    return [
                                        $district->id => "{$district->name} - {$provinceName}, {$regionName}"
                                    ];
                                }
                            })
                            ->toArray()
                        : []
                    ),

                Select::make('municipality_id')
                    ->label('Municipality')
                    ->relationship('municipality', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->reactive()
                    ->live()
                    ->multiple()
                    ->options(
                        fn($get) => !empty($get('province_id'))
                        ? Municipality::whereIn('province_id', (array) $get('province_id'))
                            ->where('name', '!=', 'Not Applicable')
                            ->orWhereHas('district', function ($query) use ($get) {
                                $query->whereIn('province_id', (array) $get('province_id'));
                            })
                            ->pluck('name', 'id')
                            ->toArray()
                        : []
                    )

                    ->afterStateUpdated(function (Set $set, $get, $state) {
                        $selectedMunicipalities = (array) $state;
                        $currentDistricts = (array) $get('district_id');

                        $filteredDistricts = District::whereIn('id', $currentDistricts)
                            ->whereIn('province_id', Municipality::whereIn('id', $selectedMunicipalities)->pluck('province_id'))
                            ->pluck('id')
                            ->toArray();

                        $set('district_id', $filteredDistricts);
                    }),



            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No users available')
            ->columns([
                TextColumn::make("name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("email")
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("roles.name")
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('region.name')
                    ->label('Region')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $region = $record->region->sortBy('name')->pluck('name')->toArray();

                        $regionHtml = array_map(function ($name, $index) use ($region) {
                            $comma = ($index < count($region) - 1) ? ', ' : '';

                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';

                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                        }, $region, array_keys($region));

                        return implode('', $regionHtml);
                    })
                    ->html(),

                TextColumn::make('province.name')
                    ->label('Province')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $province = $record->province->sortBy('name')->pluck('name')->toArray();

                        $provinceHtml = array_map(function ($name, $index) use ($province) {
                            $comma = ($index < count($province) - 1) ? ', ' : '';

                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';

                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                        }, $province, array_keys($province));

                        return implode('', $provinceHtml);
                    })
                    ->html(),

                TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $districts = $record->district->sortBy('name')->map(function ($district) {
                            $municipalityName = optional($district->underMunicipality)->name ?? '-';
                            $provinceName = optional($district->province)->name ?? '-';
                            $regionName = optional(optional($district->province)->region)->name ?? '-';

                            return $district->name .
                                ($district->underMunicipality
                                    ? " - {$municipalityName}, {$provinceName}, {$regionName}"
                                    : " - {$provinceName}, {$regionName}");
                        })->toArray();

                        $districtHtml = array_map(function ($name, $index) use ($districts) {
                            $comma = ($index < count($districts) - 1) ? ', ' : '';
                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';
                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                        }, $districts, array_keys($districts));

                        return implode('', $districtHtml);
                    })
                    ->html(),

                TextColumn::make('municipality.name')
                    ->label('Municipality')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $municipalities = $record->municipality->sortBy('name')->pluck('name')->toArray();

                        $municipalityHtml = array_map(function ($name, $index) use ($municipalities) {
                            $comma = ($index < count($municipalities) - 1) ? ', ' : '';
                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';
                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                        }, $municipalities, array_keys($municipalities));

                        return implode('', $municipalityHtml);
                    })
                    ->html(),




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

                            NotificationHandler::sendSuccessNotification('Deleted', 'User has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'User has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'User has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected users have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected users have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected users have been deleted permanently.');
                        }),
                ])
                    ->label('Select Action'),
            ]);

    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            // ->where('roles.name', '!=', 'Admin')
            ->whereNot('id', Auth::id());
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }




}

