<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Models\ScholarshipProgram;
use App\Filament\Resources\TrainingProgramResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowTrainingPrograms extends ListRecords
{
    protected static string $resource = TrainingProgramResource::class;

    public function getBreadcrumbs(): array
    {
        $scholarshipId = $this->getScholarshipProgramId();
        
        $scholarship = ScholarshipProgram::find($scholarshipId);

        return [
            route('filament.admin.resources.scholarship-programs.showTrainingPrograms', ['record' => $scholarship->id]) => $scholarship->name ?? 'Scholarship Programs',
            'Training Programs',
            'List'
        ];
    }
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getHeaderActions(): array
    {
        $scholarshipProgramId = $this->getScholarshipProgramId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.training-programs.create', ['scholarship_program_id' => $scholarshipProgramId])),
        ];
    }

    protected function getScholarshipProgramId(): ?int
    {
        return (int) request()->route('record');
    }
}