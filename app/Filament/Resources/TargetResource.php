<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetResource\Pages;
use App\Models\Target;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static ?string $navigationGroup = "MANAGE TARGET";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make("legislator_id")
                    ->required()
                    ->relationship("legislator", "name"),
                Select::make("legislator_id")
                    ->required()
                    ->relationship("legislator", "particular")
                    ->label('Particular'),
                Select::make("province_id")
                    ->required()
                    ->relationship("province", "name"),
                Select::make("province_id")
                    ->required()
                    ->relationship("province", "region_id")
                    ->label('Region'),
                Select::make("scholarship_program_id")
                    ->required()
                    ->relationship("scholarship_program", "name"),
                Select::make("tvi_id")
                    ->required()
                    ->relationship("tvi", "name")
                    ->label('Institution'),
                TextInput::make('number_of_slots')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->rules(['min:10', 'max:25'])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No targets yet')
            ->columns([
                TextColumn::make("legislator.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("legislator.particular")
                    ->label('Particular')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("province.region.name")
                    ->label('Region')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("scholarship_program.name")
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("tvi.name")
                    ->label('Institution')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('status')
                ->form([
                    Select::make('status_id')
                        ->label('Status')
                        ->options([
                            'all' => 'All',
                        'deleted' => 'Recently Deleted',
                        ])
                        ->default('all')
                        ->selectablePlaceholder(false),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['status_id'] === 'all',
                            fn (Builder $query): Builder => $query->whereNull('deleted_at')
                        )
                        ->when(
                            $data['status_id'] === 'deleted',
                            fn (Builder $query): Builder => $query->whereNotNull('deleted_at')
                        );
                }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn (): bool => self::isTrashedFilterActive()),
                    ForceDeleteBulkAction::make()
                        ->hidden(fn (): bool => !self::isTrashedFilterActive()),
                    RestoreBulkAction::make()
                        ->hidden(fn (): bool => !self::isTrashedFilterActive()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTargets::route('/'),
            'create' => Pages\CreateTarget::route('/create'),
            'edit' => Pages\EditTarget::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function isTrashedFilterActive(): bool
    {
        $filters = request()->query('tableFilters', []);
        return isset($filters['status']['status_id']) && $filters['status']['status_id'] === 'deleted';
    }
}
