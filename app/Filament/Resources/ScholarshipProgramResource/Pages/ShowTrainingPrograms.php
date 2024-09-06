<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Filament\Resources\TrainingProgramResource;
use App\Models\ScholarshipProgram;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Actions\CreateAction;

class ShowTrainingPrograms extends ListRecords
{
    protected static string $resource = TrainingProgramResource::class;

    protected ?string $heading = 'Training Programs';

    public function getBreadcrumbs(): array
    {
        $scholarshipId = $this->getScholarshipProgramId();
        
        $scholarship = ScholarshipProgram::find($scholarshipId);

        $scholarshipProgramId = $scholarship->id;

        return [
            route('filament.admin.resources.scholarship-programs.showTrainingPrograms', ['record' => $scholarshipProgramId]) => $scholarship->name ?? 'Scholarship Programs',
            'Training Programs',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
        ];
    }

    protected function getScholarshipProgramId(): ?int
    {
        return (int) request()->route('record');
    }
}

