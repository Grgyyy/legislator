<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TargetCommentResource\Pages;
use App\Models\Target;
use App\Models\TargetComment;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TargetCommentResource extends Resource
{
    protected static ?string $model = TargetComment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static bool $shouldRegisterNavigation = false;

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
            'Municipality' => $targetRecord->tvi->municipality->name ?? 'N/A',
            'Province' => $targetRecord->tvi->district->province->name ?? 'N/A',
            'Region' => $targetRecord->tvi->district->province->region->name ?? 'N/A',
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
            Section::make()
                ->schema([
                    Textarea::make('content')
                        ->label('Comment Content')
                        ->required()
                        ->rows(4)
                        ->extraAttributes(['style' => 'width: 100%;']),
                ]),

            TextInput::make('target_id')
                ->default(request()->query('record'))
                ->label('')
                ->extraAttributes(['class' => 'hidden'])
                ->readOnly(),

        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->paginated([5, 10, 25, 50])
            ->columns([
                TextColumn::make('user.name')->label('Author'),
                TextColumn::make('content'),
                TextColumn::make("updated_at")
                    ->label("Date Encoded")
                    ->formatStateUsing(
                        fn($state) =>
                        \Carbon\Carbon::parse($state)->format('M j, Y') . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
                        \Carbon\Carbon::parse($state)->format('h:i A')
                    )
                    ->html()
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->label('Select Action'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTargetComments::route('/'),
            'create' => Pages\CreateTargetComment::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $routeParameter = request()->route('record');

        if (!request()->is('*/edit') && $routeParameter && is_numeric($routeParameter)) {
            $query->where('target_id', (int) $routeParameter);
        }

        return $query->orderBy('updated_at', 'desc');
    }
}
