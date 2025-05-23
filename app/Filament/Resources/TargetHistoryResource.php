<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetHistoryResource\Pages;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use App\Models\Tvi;
use DB;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('fund_source')
                    ->label('Fund Source')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No legislator available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No particular available';
                        }

                        $particular = $record->allocation->particular;
                        $subParticular = $particular->subParticular;
                        $fundSource = $subParticular ? $subParticular->fundSource : null;

                        return $fundSource ? $fundSource->name : 'No fund source available';
                    }),

                TextColumn::make('allocation.legislator.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Source of Fund')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('appropriation_type')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.year')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.legislator.particular.subParticular')
                    ->label('Particular')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $legislator = $record->allocation->legislator;

                        if (!$legislator) {
                            return 'No legislator available';
                        }

                        $particulars = $legislator->particular;

                        if ($particulars->isEmpty()) {
                            return 'No particular available';
                        }

                        $particular = $particulars->first();
                        $district = $particular->district;
                        $municipality = $district ? $district->underMunicipality : null;

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
                    }),

                TextColumn::make('municipality.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('district.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('district.province.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('district.province.region.name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tvi_name')
                    ->label('Institution')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn($state) => preg_replace_callback('/(\d)([a-zA-Z])/', fn($matches) => $matches[1] . strtoupper($matches[2]), ucwords($state))),

                // TextColumn::make('tvi.tviType.name')
                //     ->label('Institution Type')
                //     ->searchable()
                //     ->toggleable()
                //     ->getStateUsing(function ($record) {
                //         $tvi = Tvi::withTrashed()->where('id', $record->tvi_id)->first();
                //         return $tvi->tviType->name;
                //     }),

                TextColumn::make('tvi.tviClass.name')
                    ->label('Institution Class')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(function ($record) {
                        $tvi = Tvi::withTrashed()->where('id', $record->tvi_id)->first();
                        return "{$tvi->tviType->name} - {$tvi->tviClass->name}";
                    }),

                TextColumn::make('qualification_title_code')
                    ->label('Qualification Code')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title_soc_code')
                    ->label('Qualification Code')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title_name')
                    ->label('Qualification Title')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return $state;
                        }

                        $state = ucwords($state);

                        if (preg_match('/\bNC\s+[I]{1,3}\b/i', $state)) {
                            $state = preg_replace_callback('/\bNC\s+([I]{1,3})\b/i', function ($matches) {
                                return 'NC ' . strtoupper($matches[1]);
                            }, $state);
                        }

                        return $state;
                    }),

                TextColumn::make('abdd.name')
                    ->label('ABDD Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.tvet.name')
                    ->label('TVET Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('deliveryMode.name')
                    ->label('Delivery Mode')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('learningMode.name')
                    ->label('Learning Mode')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('number_of_slots')
                    ->label('Number of Slots')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->searchable()
                    ->toggleable()
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label("Processor's Name")
                    ->searchable()
                    ->toggleable(),

                TextColumn::make("updated_at")
                    ->label("Date Encoded")
                    ->formatStateUsing(
                        fn($state) =>
                        \Carbon\Carbon::parse($state)->format('M j, Y') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
                        \Carbon\Carbon::parse($state)->format('h:i A')
                    )
                    ->html()
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    // Action::make('restore')
                    //     ->label('Restore')
                    //     ->icon('heroicon-o-arrow-uturn-left')
                    //     ->action(fn (TargetHistory $record) => self::restoreTarget($record)) // Call the separate function
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Restore Target')
                    //     ->modalSubheading('Are you sure you want to restore this target?')
                    //     ->modalButton('Restore'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ])
                    ->label('Select Action'),
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
        DB::transaction(function () use ($record) {
            $targetRecord = Target::find($record->target_id);

            if (!$targetRecord) {
                Filament::notify('error', 'Target record not found.');
                return;
            }

            $pendingStatus = TargetStatus::where('desc', 'Pending')->first();

            if (!$pendingStatus) {
                Filament::notify('error', 'Pending status not found.');
                return;
            }

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

            $targetRecord->save();

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
