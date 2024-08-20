<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllocationResource\Pages;
use App\Models\Allocation;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('legislator_id')
                    ->relationship('legislator', 'name')
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('particular_id', null); // Reset particular when legislator changes
                        $set('particular_options', self::getParticularOptions($state)); // Set particular options
                    }),

                    Select::make('particular_id')
                    ->options(fn ($get) => self::getParticularOptions($get('legislator_id')))
                    ->reactive()
                    ->searchable(),

                Select::make('scholarship_program_id')
                    ->relationship("scholarship_program", "name"),

                TextInput::make('allocation')
                    ->label('Allocation')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0)
                    ->unique(ignoreRecord: true)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2)
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('admin_cost', $state * 0.02);
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
            ]);
    }

    private static function getParticularOptions($legislatorId)
    {
        if (!$legislatorId) {
            return [];
        }
        
        // Fetch the legislator with their particulars and related data
        $legislator = \App\Models\Legislator::with('particular.district.municipality')
            ->find($legislatorId);
        
        if (!$legislator) {
            return [];
        }
    
        // Create an array of particulars with the formatted string and their ID
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
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("scholarship_program.name")
                    ->label('Scholarship Program')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make("allocation")
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2, '.', ',')),

                TextColumn::make("admin_cost")
                    ->label('Admin Cost')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2, '.', ',')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersTriggerAction(
                fn(\Filament\Actions\StaticAction $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
}
