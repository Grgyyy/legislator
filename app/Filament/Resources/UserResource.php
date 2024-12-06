<?php

namespace App\Filament\Resources;

use Closure;
use App\Models\User;
use App\Models\Region;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Province;

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
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Name'),

                TextInput::make("email")
                    ->placeholder('Enter user email')
                    ->email()
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Email'),

                TextInput::make("password")
                    ->placeholder('Enter password')
                    ->password()
                    ->revealable()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->regex('/^[A-Za-z0-9]+$/')
                    ->minValue(8)
                    ->validationAttribute('Password')
                    ->validationMessages([
                        'regex' => 'The password must not contain special characters.',
                        'min' => 'Password must be at least 8 characters long.'
                    ]),
                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),

                Select::make('region_id')
                    ->label('Region')
                    ->options(Region::all()->pluck('name', 'id'))
                    ->searchable(),

                Select::make('province_id')
                    ->label('Province')
                    ->options(Province::all()->pluck('name', 'id'))
                    ->searchable(),


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

                TextColumn::make("region.name")
                    ->label('Region')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("province.name")
                    ->label('Province')
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
                ]),
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
