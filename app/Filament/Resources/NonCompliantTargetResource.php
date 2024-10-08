<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NonCompliantTargetResource\Pages;
use App\Filament\Resources\NonCompliantTargetResource\RelationManagers;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\NonCompliantRemark;
use App\Models\NonCompliantTarget;
use App\Models\Particular;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\Target;
use App\Models\TargetRemark;
use App\Models\Tvi;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NonCompliantTargetResource extends Resource
{
    protected static ?string $model = NonCompliantRemark::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    
    protected static ?string $navigationGroup = 'MANAGE TARGET';

    protected static ?string $navigationLabel = "Non-Compliant Targets";

    public static function form(Form $form): Form
    {
        $targetIdParams = request()->query('record');
        $targetRecord = $targetIdParams ? Target::find($targetIdParams) : null;

        $targetData = $targetRecord ? [
            'Fund Source' => $targetRecord->allocation->particular->subParticular->FundSource->name ?? 'N/A',
            'Legislator' => $targetRecord->allocation->legislator->name ?? 'N/A',
            'Soft/Commitment' => $targetRecord->allocation->soft_or_commitment ?? 'N/A',
            'Allocation Type' => $targetRecord->appropriation_type ?? 'N/A',
            'Allocation Year' => $targetRecord->allocation->year ?? 'N/A',
            'Particular ID' => $targetRecord->allocation->particular->subParticular->name ?? 'N/A',
            'District' => $targetRecord->tvi->district->name ?? 'N/A',
            'Municipality' => $targetRecord->tvi->district->municipality->name ?? 'N/A',
            'Province' => $targetRecord->tvi->district->municipality->province->name ?? 'N/A',
            'Region' => $targetRecord->tvi->district->municipality->province->region->name ?? 'N/A',
            'Institution' => $targetRecord->tvi->name ?? 'N/A',
            'Institution Type' => $targetRecord->tvi->tviClass->tviType->name ?? 'N/A',
            'Class A Institution' => $targetRecord->tvi->tviClass->name ?? 'N/A',
            'Class B Institution' => $targetRecord->tvi->InstitutionClass->name ?? 'N/A',
            'Qualification Title' => $targetRecord->qualification_title->trainingProgram->title ?? 'N/A',
            'Scholarship Program' => $targetRecord->qualification_title->scholarshipProgram->name ?? 'N/A',
            'Ten Priority Sector' => $targetRecord->qualification_title->trainingProgram->priority->name ?? 'N/A',
            'TVET Sector' => $targetRecord->qualification_title->trainingProgram->tvet->name ?? 'N/A',
            'ABDD Sector' => $targetRecord->abdd->name ?? 'N/A',
            'Number of Slots' => $targetRecord->number_of_slots ?? 'N/A',
            'Per Capita Cost' => $targetRecord->qualification_title->pcc ?? 'N/A',
            'Total Amount' => $targetRecord->total_amount ?? 'N/A',
        ] : [];

        $textInputs = [];
        foreach ($targetData as $key => $value) {
            if ($key === 'Per Capita Cost' || $key === 'Total Amount') {
                $value = '₱' . number_format($value, 2);
            }

            $textInputs[] = TextInput::make($key)
                ->label($key)
                ->default($value)
                ->readOnly();
        }

        return $form->schema([
            Section::make()
                ->columns(5)
                ->schema($textInputs),
            Select::make('target_remarks_id')
                ->relationship('target_remarks', 'remarks')
                ->required(),
            TextInput::make('others_remarks')
                ->label('Please specify:'),
            TextInput::make('target_id')
                ->label('')
                ->default($targetIdParams)
                ->extraAttributes(['class' => 'hidden'])
                ->readOnly(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('allocation.particular.subParticular.fundSource.name')
                    ->label('Allocation Type'),
                TextColumn::make('allocation.legislator.name')
                    ->label('Legislator'),
                TextColumn::make('allocation.particular.subParticular.name')
                    ->label('Particular'),
                TextColumn::make('allocation.soft_or_commitment')
                    ->label('Soft/Commitment'),
                TextColumn::make('appropriation_type')
                    ->label('Appropriation Type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('allocation.year')
                    ->label('Allocation Year')
                    ->searchable()
                    ->toggleable(),
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
                    ->label('Institution'),
                TextColumn::make('allocation.scholarship_program.name')
                    ->label('Scholarship Program'),
                TextColumn::make('qualification_title.trainingProgram.title')
                    ->label('Qualification Title'),
                TextColumn::make('qualification_title.trainingProgram.priority.name')
                    ->label('Priority Sector'),
                TextColumn::make('qualification_title.trainingProgram.tvet.name')
                    ->label('TVET Sector'),
                TextColumn::make('abdd.name')
                    ->label('ABDD Sector'),
                TextColumn::make('number_of_slots')
                    ->searchable()
                    ->toggleable()
                    ->label('No. of Slots'),
                TextColumn::make('total_amount')
                    ->searchable()
                    ->toggleable()
                    ->label('Total Amount')
                    ->prefix('₱')
                    ->formatStateUsing(fn($state) => number_format($state, 2, '.', ',')),
                TextColumn::make('targetStatus.desc')
                    ->label('Status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListNonCompliantTargets::route('/'),
            'create' => Pages\CreateNonCompliantTarget::route('/create'),
            'edit' => Pages\EditNonCompliantTarget::route('/{record}/edit'),
        ];
    }

    protected static function getParticularOptions($legislatorId)
    {
        $particulars = Particular::whereHas('allocation', function ($query) use ($legislatorId) {
            $query->where('legislator_id', $legislatorId);
        })
            ->with('subParticular')
            ->get()
            ->mapWithKeys(function ($particular) {

                if ($particular->district->name === 'Not Applicable') {
                    if ($particular->subParticular->name === 'Partylist') {
                        return [$particular->id => $particular->subParticular->name . " - " . $particular->partylist->name];
                    } else {
                        return [$particular->id => $particular->subParticular->name];
                    }
                } else {
                    return [$particular->id => $particular->subParticular->name . " - " . $particular->district->name . ', ' . $particular->district->municipality->name];
                }

            })
            ->toArray();

        return empty($particulars) ? ['' => 'No Particular Available'] : $particulars;
    }

    protected static function getAppropriationTypeOptions($year) {
        $yearNow = date('Y');
    
        if ($year == $yearNow) {
            return ["Current" => "Current"];
        } elseif ($year == $yearNow - 1) {
            return ["Continuing" => "Continuing"];
        } else {
            return ["Unknown" => "Unknown"];
        }
    }

    protected static function getScholarshipProgramsOptions($legislatorId, $particularId)
    {
        $scholarshipPrograms = ScholarshipProgram::whereHas('allocation', function ($query) use ($legislatorId, $particularId) {
            $query->where('legislator_id', $legislatorId)
                ->where('particular_id', $particularId);
        })->pluck('name', 'id')->toArray();

        return empty($scholarshipPrograms) ? ['' => 'No Scholarship Program Available'] : $scholarshipPrograms;
    }

    protected static function getAllocationYear($legislatorId, $particularId, $scholarshipProgramId)
    {
        $yearNow = date('Y');
        $allocations = Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->whereIn('year', [$yearNow, $yearNow - 1])
            ->pluck('year', 'year')
            ->toArray();

        return empty($allocations) ? ['' => 'No Allocation Available.'] : $allocations;
    }

    protected static function getQualificationTitles($scholarshipProgramId)
    {
        return QualificationTitle::where('scholarship_program_id', $scholarshipProgramId)
            ->where('status_id', 1)
            ->whereNull('deleted_at')
            ->with('trainingProgram')
            ->get()
            ->pluck('trainingProgram.title', 'id')
            ->toArray();
    }

    protected static function getAbddSectors($tviId)
    {
        $tvi = Tvi::with(['district.municipality.province'])->find($tviId);

        if (!$tvi || !$tvi->district || !$tvi->district->municipality || !$tvi->district->municipality->province) {
            return ['' => 'No ABDD Sectors Available.'];
        }

        $abddSectors = $tvi->district->municipality->province->abdds()
            ->select('abdds.id', 'abdds.name')
            ->pluck('name', 'id')
            ->toArray();

        return empty($abddSectors) ? ['' => 'No ABDD Sectors Available.'] : $abddSectors;
    }

    public function getFormattedParticularAttribute()
    {
        $particular = $this->allocation->particular ?? null;

        if (!$particular) {
            return 'No Particular Available';
        }

        $district = $particular->district;
        $municipality = $district ? $district->municipality : null;
        $province = $municipality ? $municipality->province : null;

        $districtName = $district ? $district->name : 'Unknown District';
        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
        $provinceName = $province ? $province->name : 'Unknown Province';

        $subParticular = $particular->subParticular->name ?? 'Unknown Sub-Particular';

        if ($subParticular === 'Partylist') {
            return "{$subParticular} - {$particular->partylist->name}";
        } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
            return "{$subParticular}";
        } else {
            return "{$subParticular} - {$districtName}, {$municipalityName}";
        }
    }


    protected function getFormattedTotalAmountAttribute($total_amount)
    {
        return '₱' . number_format($this->$total_amount, 2, '.', ',');
    }

    protected function getFormattedPerCapitaCostAttribute($total_training_cost_pcc)
    {
        return '₱' . number_format($this->$total_training_cost_pcc, 2, '.', ',');
    }

    protected function getFormattedScholarshipProgramAttribute($allocation)
    {
        return $this->$allocation->scholarship_program->name ?? 'No Scholarship Program Available';
    }
    protected function getFundSource($abddSectorsallocation)
    {
        $legislator = $this->$$abddSectorsallocation->legislator;

        if (!$legislator) {
            return 'No Legislator Available';
        }

        $particulars = $legislator->particular;

        if ($particulars->isEmpty()) {
            return 'No Particular Available';
        }

        $particular = $this->$abddSectorsallocation->particular;
        $subParticular = $particular->subParticular;
        $fundSource = $subParticular ? $subParticular->fundSource : null;

        return $fundSource ? $fundSource->name : 'No Fund Source Available';
    }
}
