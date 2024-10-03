<?php

namespace App\Filament\Resources\TargetResource\Pages;

use App\Filament\Resources\TargetResource;
use App\Models\Allocation;
use App\Models\QualificationTitle;
use App\Models\TargetHistory;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Forms;

class Compliant extends EditRecord
{
    protected static string $resource = TargetResource::class;

    protected static ?string $title = 'Compliant Target';

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.compliant-targets.index') => 'Compliant Targets',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $allocation = $record->allocation;
        
        // Initialize data from the existing record
        $data['legislator_id'] = $data['legislator_id'] ?? $allocation->legislator_id;
        $data['particular_id'] = $data['particular_id'] ?? $allocation->particular_id;
        $data['scholarship_program_id'] = $data['scholarship_program_id'] ?? $allocation->scholarship_program_id;
        $data['allocation_year'] = $data['allocation_year'] ?? $allocation->year;

        return $data;
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('legislator_id')
                ->label('Legislator')
                ->relationship('allocation.legislator', 'name')
                ->required(),

            Forms\Components\Select::make('particular_id')
                ->label('Particular')
                ->relationship('allocation.particular', 'name')
                ->required(),

            Forms\Components\Select::make('scholarship_program_id')
                ->label('Scholarship Program')
                ->relationship('allocation.scholarshipProgram', 'name')
                ->required(),

            Forms\Components\TextInput::make('number_of_slots')
                ->label('Number of Slots')
                ->required()
                ->numeric()
                ->min(1),

            Forms\Components\Select::make('qualification_title_id')
                ->label('Qualification Title')
                ->relationship('qualificationTitle', 'title')
                ->required(),

            // Add more fields as needed

            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->nullable(),
        ];
    }


}
