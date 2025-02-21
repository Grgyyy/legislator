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
                    ->options(Region::all()->pluck('name', 'id')->toArray())
                    ->afterStateUpdated(function (Set $set) {
                        $set('province_id', null);
                        $set('municipality_id', null);
                        $set('district_id', null);
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
                        ? Province::whereIn('region_id', (array) $get('region_id'))->pluck('name', 'id')->toArray()
                        : []
                    )
                    ->afterStateUpdated(function (Set $set) {
                        $set('municipality_id', null);
                        $set('district_id', null);
                    }),
                // ->rule(function ($get) {
                //     return function ($attribute, $value, $fail) use ($get) {
                //         $regionIds = (array) $get('region_id');

                //         if (!empty($value) && !empty($regionIds)) {
                //             foreach ($value as $provinceId) {
                //                 $province = Province::find($provinceId);
                //                 if ($province && !in_array($province->region_id, $regionIds)) {
                //                     $fail("The selected province must belong to the selected region.");
                //                 }
                //             }
                //         }
                //     };
                // }),

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
                        fn($get) =>
                        !empty($get('province_id'))
                        ? Municipality::whereIn('municipalities.province_id', (array) $get('province_id'))
                            ->when(!empty($get('district_id')), function ($query) use ($get) {
                                $query->whereHas('district', function ($q) use ($get) {
                                    $q->whereIn('districts.id', (array) $get('district_id'));
                                });
                            })
                            ->where('municipalities.name', '!=', 'Not Applicable')
                            ->distinct()
                            ->pluck('municipalities.name', 'municipalities.id')
                            ->toArray()
                        : []
                    ),

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
                        fn($get) =>
                        !empty($get('province_id'))
                        ? District::whereIn('districts.province_id', (array) $get('province_id'))
                            ->when(!empty($get('municipality_id')), function ($query) use ($get) {
                                $query->whereHas('municipality', function ($q) use ($get) {
                                    $q->whereIn('municipalities.id', (array) $get('municipality_id'));
                                });
                            })
                            ->where('districts.name', '!=', 'Not Applicable')
                            ->distinct()
                            ->pluck('districts.name', 'districts.id')
                            ->toArray()
                        : []
                    ),




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

                TextColumn::make('district.name')
                    ->label('District')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($record) {
                        $districts = $record->district->sortBy('name')->pluck('name')->toArray();

                        $districtHtml = array_map(function ($name, $index) use ($districts) {
                            $comma = ($index < count($districts) - 1) ? ', ' : '';
                            $lineBreak = (($index + 1) % 3 == 0) ? '<br>' : '';
                            $paddingTop = ($index % 3 == 0 && $index > 0) ? 'padding-top: 15px;' : '';

                            return "<div style='{$paddingTop} display: inline;'>{$name}{$comma}{$lineBreak}</div>";
                        }, $districts, array_keys($districts));

                        return implode('', $districtHtml);
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

