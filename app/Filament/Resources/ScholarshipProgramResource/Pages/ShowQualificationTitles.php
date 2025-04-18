<?php
namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Filament\Resources\QualificationTitleResource;
use App\Models\ScholarshipProgram;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ShowQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
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

    protected function getscholarshipProgramId(): ?int
    {
        return (int) request()->route('record');
    }
}