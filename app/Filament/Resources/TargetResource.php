<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetResource\Pages;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\ScholarshipProgram;
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
                Select::make('legislator_id')
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
                        // Clear scholarship_id when legislator changes
                        $set('scholarship_id', null);
                        // Set scholarship options based on the new legislator
                        $set('scholarshipOptions', self::getScholarshipProgramsOptions($state));
                    }),

                Select::make('scholarship_id')
                    ->label('Scholarship Program')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn($get) => self::getScholarshipProgramsOptions($get('legislator_id')))
                    ->extraAttributes(['data-no-allocation' => 'no-allocation'])
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // Optionally handle updates to scholarship_id
                        $set('scholarship_id', $state);
                    }),
                Select::make("qualification_title_id")
                    ->required()
                    ->relationship("qualification_title.trainingProgram", "title")
                    ->label('Qualification Title'),
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
                TextColumn::make("allocation.legislator.particular.name")
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No Legislator Available';
                        }

                        // Assuming `particular` is a collection
                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No Particular Available';
                        }

                        // Fetch the first particular or handle as needed
                        $particular = $particulars->first();

                        $district = $particular->district;
                        $municipality = $district ? $district->municipality : null;

                        $districtName = $district ? $district->name : 'Unknown District';
                        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';

                        $formattedName = $districtName === 'Not Applicable'
                            ? $particular->name
                            : "{$particular->name} - {$districtName}, {$municipalityName}";

                        return $formattedName;
                        })
                    ->searchable()
                    ->toggleable()
                    ->label('Particular'),
                TextColumn::make("tvi.name")
                    ->searchable()
                    ->toggleable()
                    ->label('Institution'),
                TextColumn::make("qualification_title.training_program.title")
                    ->label('Qualification Title')
                        ->getStateUsing(function ($record) {
                            // Access the qualification_title and its related training program
                            $qualificationTitle = $record->qualification_title;

                            if (!$qualificationTitle) {
                                return 'No Qualification Title Available';
                            }

                            $trainingProgram = $qualificationTitle->trainingProgram; // Adjust to match the actual relationship name

                            if (!$trainingProgram) {
                                return 'No Training Program Available';
                            }

                            return $trainingProgram->title;
                        }),
                TextColumn::make("allocation.scholarship_program.name")
                    ->label('Scholarship Program'),

                TextColumn::make("priority.name")
                    ->searchable()
                    ->toggleable()
                    ->label('Top Ten Priority Sector'),
                TextColumn::make("abdd.name")
                    ->searchable()
                    ->toggleable()
                    ->label('ABDD Sector'),
                TextColumn::make("tvet.name")
                    ->searchable()
                    ->toggleable()
                    ->label('TVET Sector'),
                TextColumn::make("number_of_slots")
                    ->searchable()
                    ->toggleable()
                    ->label('No. of Slots'),
                TextColumn::make("total_amount")
                    ->searchable()
                    ->toggleable()
                    ->label('Total Amount'),


                TextColumn::make("status.desc")
                    ->searchable()
                    ->toggleable()
                    ->label('TVET Sector'),

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

    protected static function getScholarshipProgramsOptions($legislatorId)
    {
        // Retrieve all allocations for the given legislator
        $allocations = Allocation::where('legislator_id', $legislatorId)->get();

        if ($allocations->isEmpty()) {
            return ['' => 'No Allocation Available'];
        }

        // Get unique scholarship program IDs from the allocations
        $scholarshipProgramIds = $allocations->pluck('scholarship_program_id')->unique();

        // Retrieve the scholarship programs associated with these IDs
        $scholarshipPrograms = ScholarshipProgram::whereIn('id', $scholarshipProgramIds)
            ->pluck('name', 'id')
            ->toArray();

        // Add default option if no scholarship programs are available
        if (empty($scholarshipPrograms)) {
            $scholarshipPrograms = ['' => 'No Allocation Available'];
        }

        return $scholarshipPrograms;
    }
}
