<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetResource\Pages;
use App\Models\Allocation;
use App\Models\Legislator;
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
                Select::make('allocation_id')
                    ->label('Legislator')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return Legislator::all()->mapWithKeys(function ($legislator) {
                            return [$legislator->id => $legislator->name];
                        });
                    })
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('scholarship_id', null);
                        $set('scholarshipOptions', self::getScholarshipProgramsOptions($state));
                    }),

                Select::make('scholarship_id')
                    ->label('Scholarship Program')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn($get) => self::getScholarshipProgramsOptions($get('allocation_id')))
                    ->extraAttributes(['data-no-allocation' => 'no-allocation'])
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // This will run after the state has been updated
                        $set('scholarship_id', $state);
                    }),
                Select::make("tvi_id")
                    ->required()
                    ->relationship("tvi", "name")
                    ->label('Institution'),
                Select::make("priority_id")
                    ->required()
                    ->relationship("priority", "name"),
                Select::make("tvet_id")
                    ->required()
                    ->relationship("tvet", "name"),
                Select::make("abdd_id")
                    ->required()
                    ->relationship("abdd", "name"),
                TextInput::make('number_of_slots')
                    ->required()
                    ->autocomplete(false)
                    ->numeric()
                    ->rules(['min:10', 'max:25']),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No targets yet')
            ->columns([
                TextColumn::make("allocation.legislator.name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("allocation.legislator.particular")
                    ->label('Particular')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make("allocation.legislator.district.name")
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
                        ->hidden(fn ($record) => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
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

    protected static function getScholarshipProgramsOptions($allocationId)
    {
        $options = Allocation::where('legislator_id', $allocationId)
            ->with('scholarshipProgram')
            ->get()
            ->pluck('scholarshipProgram.name', 'scholarshipProgram.id')
            ->toArray();

        // Add default option if no scholarship programs are available
        if (empty($options)) {
            $options = ['' => 'No Allocation Available'];
        }

        return $options;
    }
}
