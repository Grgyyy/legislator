<?php
namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Models\ScholarshipProgram;
use App\Filament\Resources\QualificationTitleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    public function getBreadcrumbs(): array
    {
        $scholarshipProgramId = $this->getscholarshipProgramId();

        $scholarshipProgram = ScholarshipProgram::find($scholarshipProgramId);

        return [
            route('filament.admin.resources.scholarship-program.show_qualification_titles', ['record' => $scholarshipProgramId]) => $scholarshipProgram ? $scholarshipProgram->name : 'Scholarship Program',
            'Qualification Titles',
            'List'
        ];
    }

    protected function getHeaderActions(): array
    {
        $scholarshipProgramId = $this->getscholarshipProgramId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.qualification_titles.create', ['scholarshipProgram_id' => $scholarshipProgramId]))
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

    protected function getscholarshipProgramId(): ?int
    {
        return (int) request()->route('record');
    }
}