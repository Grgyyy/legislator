<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetHistoryResource\Pages;
use App\Filament\Resources\TargetHistoryResource\RelationManagers;
use App\Models\TargetHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
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


}
