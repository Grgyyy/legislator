<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NonCompliantRemarkResource\Pages;
use App\Filament\Resources\NonCompliantRemarkResource\RelationManagers;
use App\Models\NonCompliantRemark;
use App\Models\Target;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NonCompliantRemarkResource extends Resource
{
    protected static ?string $model = NonCompliantRemark::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            // Check if the key is "Per Capita Cost" or "Total Amount" and format accordingly
            if ($key === 'Per Capita Cost' || $key === 'Total Amount') {
                $value = '₱' . number_format($value, 2); // Format as currency
            }

            $textInputs[] = TextInput::make($key)
                ->label($key)
                ->default($value)
                ->readOnly();
        }

        return $form
            ->schema([
                Section::make()
                ->columns(5) // Adjust number of columns as needed
                ->schema($textInputs),
                Select::make('target_remarks_id')
                    ->relationship('remarks', 'remarks')
                    ->required(),
                TextInput::make('others_remarks')
                    ->label('Please specify:'),
                TextInput::make('target_id')
                    ->default(request()->query('record'))
                    ->hidden(fn ($record) => $record !== null)
                    ->readOnly(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListNonCompliantRemarks::route('/'),
            'create' => Pages\CreateNonCompliantRemark::route('/create'),
            'edit' => Pages\EditNonCompliantRemark::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('target_id', (int) $routeParameter);
        }

        return $query;
    }
}
