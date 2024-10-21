<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetHistoryResource\Pages;
use App\Filament\Resources\TargetHistoryResource\RelationManagers;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use DB;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;

class TargetHistoryResource extends Resource
{
    protected static ?string $model = TargetHistory::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fund_source')
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No Legislator Available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No Particular Available';
                        }

                        $particular = $record->allocation->particular;
                        $subParticular = $particular->subParticular;
                        $fundSource = $subParticular ? $subParticular->fundSource : null;

                        return $fundSource->name;
                    })
                    ->searchable()
                    ->toggleable()
                    ->label('Fund Source'),
                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Soft/Commitment')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('appropriation_type')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.year')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.legislator.particular.subParticular')
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No Legislator Available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No Particular Available';
                        }

                        $particular = $particulars->first();
                        $district = $particular->district;
                        $municipality = $district ? $district->municipality : null;

                        $districtName = $district ? $district->name : 'Unknown District';
                        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';

                        if ($districtName === 'Not Applicable') {
                            if ($particular->subParticular && $particular->subParticular->name === 'Party-list') {
                                return "{$particular->subParticular->name} - {$particular->partylist->name}";
                            } else {
                                return $particular->subParticular->name ?? 'Unknown SubParticular';
                            }
                        } else {
                            return "{$particular->subParticular->name} - {$districtName}, {$municipalityName}";
                        }
                    })
                    ->searchable()
                    ->toggleable()
                    ->label('Particular'),
                TextColumn::make('tvi.district.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.district.municipality.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.district.municipality.province.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.district.municipality.province.region.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('abdd.name')
                    ->searchable()
                    ->toggleable()
                    ->label('ABDD Sector'),
                TextColumn::make('tvi.name')
                    ->searchable()
                    ->toggleable()
                    ->label('Institution'),
                TextColumn::make('tvi.tviClass.tviType.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tvi.tviClass.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('qualification_title.training_program.title')
                    ->label('Qualification Title')
                    ->getStateUsing(function ($record) {
                        $qualificationTitle = $record->qualification_title;

                        if (!$qualificationTitle) {
                            return 'No Qualification Title Available';
                        }

                        $trainingProgram = $qualificationTitle->trainingProgram;

                        return $trainingProgram ? $trainingProgram->title : 'No Training Program Available';
                    }),
                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program'),
                TextColumn::make('number_of_slots')
                    ->searchable()
                    ->toggleable()
                    ->label('No. of Slots'),
                TextColumn::make('qualification_title.pcc')
                    ->searchable()
                    ->toggleable()
                    ->label('Per Capita Cost')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make('total_amount')
                    ->searchable()
                    ->toggleable()
                    ->label('Total Amount')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make('created_at')
                    ->searchable()
                    ->toggleable()
                    ->label('Date Modified')
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('F j, Y')),
                TextColumn::make('description')
                    ->label('Description')
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('restore')
                        ->label('Restore')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->action(fn (TargetHistory $record) => self::restoreTarget($record)) // Call the separate function
                        ->requiresConfirmation()
                        ->modalHeading('Restore Target')
                        ->modalSubheading('Are you sure you want to restore this target?')
                        ->modalButton('Restore'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListTargetHistories::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('target_id', (int) $routeParameter);
        }

        return $query->orderBy('updated_at', 'desc');
    }

    protected static function restoreTarget(TargetHistory $record)
    {
        // Use a transaction to ensure atomicity
        DB::transaction(function () use ($record) {
            // Find the target record using the target_id from the TargetHistory
            $targetRecord = Target::find($record->target_id);

            // Ensure the target record exists before proceeding
            if (!$targetRecord) {
                Filament::notify('error', 'Target record not found.');
                return;
            }

            // Fetch the 'Pending' status
            $pendingStatus = TargetStatus::where('desc', 'Pending')->first();

            // Make sure the status exists
            if (!$pendingStatus) {
                Filament::notify('error', 'Pending status not found.');
                return;
            }

            // Assign the values from the TargetHistory record to the Target record
            $targetRecord->fill([
                'allocation_id' => $record->allocation_id,
                'tvi_id' => $record->tvi_id,
                'abdd_id' => $record->abdd->id,
                'qualification_title_id' => $record->qualification_title_id,
                'number_of_slots' => $record->number_of_slots,
                'total_training_cost_pcc' => $record->total_training_cost_pcc,
                'total_cost_of_toolkit_pcc' => $record->total_cost_of_toolkit_pcc,
                'total_training_support_fund' => $record->total_training_support_fund,
                'total_assessment_fee' => $record->total_assessment_fee,
                'total_entrepreneurship_fee' => $record->total_entrepreneurship_fee,
                'total_new_normal_assisstance' => $record->total_new_normal_assisstance,
                'total_accident_insurance' => $record->total_accident_insurance,
                'total_book_allowance' => $record->total_book_allowance,
                'total_uniform_allowance' => $record->total_uniform_allowance,
                'total_misc_fee' => $record->total_misc_fee,
                'total_amount' => $record->total_amount,
                'appropriation_type' => $record->appropriation_type,
                'target_status_id' => $pendingStatus->id,
            ]);

            // Save the target record
            $targetRecord->save();

            // Create a new TargetHistory record to log the restoration
            TargetHistory::create([
                'target_id' => $record->id,
                'allocation_id' => $record->allocation_id,
                'tvi_id' => $record->tvi_id,
                'qualification_title_id' => $record->qualification_title_id,
                'abdd_id' => $record->abdd_id,
                'number_of_slots' => $record->number_of_slots,
                'total_training_cost_pcc' => $record->total_training_cost_pcc,
                'total_cost_of_toolkit_pcc' => $record->total_cost_of_toolkit_pcc,
                'total_training_support_fund' => $record->total_training_support_fund,
                'total_assessment_fee' => $record->total_assessment_fee,
                'total_entrepreneurship_fee' => $record->total_entrepreneurship_fee,
                'total_new_normal_assisstance' => $record->total_new_normal_assisstance,
                'total_accident_insurance' => $record->total_accident_insurance,
                'total_book_allowance' => $record->total_book_allowance,
                'total_uniform_allowance' => $record->total_uniform_allowance,
                'total_misc_fee' => $record->total_misc_fee,
                'total_amount' => $record->total_amount,
                'appropriation_type' => $record->appropriation_type,
                'target_status_id' => $record->target_status_id,
                'description' => 'Target Restored',
            ]);
        });
    }

}
