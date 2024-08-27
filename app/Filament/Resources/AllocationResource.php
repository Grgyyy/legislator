<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllocationResource\Pages;
use App\Models\Allocation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('legislator_id')
                    ->relationship('legislator', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('particular_id', null);
                        $set('particular_options', self::getParticularOptions($state));
                    }),
                Select::make('particular_id')
                    ->label('Particular')
                    ->options(fn($get) => self::getParticularOptions($get('legislator_id')))
                    ->required()
                    ->reactive()
                    ->searchable()
                    ->label('Particular'),
                Select::make('scholarship_program_id')
                    ->relationship("scholarship_program", "name")
                    ->required(),
                TextInput::make('allocation')
                    ->label('Allocation')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('admin_cost', $state * 0.02);
                        $set('balance', $state);
                    }),
                TextInput::make('admin_cost')
                    ->label('Admin Cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->readOnly()
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->numeric()
                    ->rules(['min:' . date('Y'), 'digits: 4',])
                    ->default(date('Y')),
                TextInput::make('balance')
                    ->label('')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->extraAttributes(['style' => 'display: none;'])
                    ->reactive(),
            ]);
    }



    private static function getParticularOptions($legislatorId)
    {
        if (!$legislatorId) {
            return [];
        }

        $legislator = \App\Models\Legislator::with('particular.district.municipality')
            ->find($legislatorId);

        if (!$legislator) {
            return [];
        }

        return $legislator->particular->mapWithKeys(function ($particular) {
            $districtName = $particular->district->name ?? 'Unknown District';
            $municipalityName = $particular->district->municipality->name ?? 'Unknown Municipality';
            $formattedName = "{$particular->name} - {$districtName}, {$municipalityName}";

            return [$particular->id => $formattedName];
        })->toArray();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No allocations yet')
            ->columns([
                TextColumn::make("legislator.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("particular.name")
                    ->getStateUsing(function ($record) {
                        $particular = $record->particular;

                        if (!$particular) {
                            return 'No Particular Available';
                        }

                        $district = $particular->district;
                        $municipality = $district ? $district->municipality : null;
                        $districtName = $district ? $district->name : 'Unknown District';
                        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
                        $formattedName = "{$particular->name} - {$districtName}, {$municipalityName}";

                        return $formattedName;
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("scholarship_program.name")
                    ->label('Scholarship Program')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("allocation")
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make("admin_cost")
                    ->label('Admin Cost')
                    ->sortable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make("year")
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllocations::route('/'),
            'create' => Pages\CreateAllocation::route('/create'),
            'edit' => Pages\EditAllocation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->orderBy('year', 'desc');
    }
}
