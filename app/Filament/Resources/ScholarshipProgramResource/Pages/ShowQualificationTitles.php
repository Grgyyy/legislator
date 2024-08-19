<?php
namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Filament\Resources\QualificationTitleResource;
use App\Models\ScholarshipProgram;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use App\Filament\Resources\RegionResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShowQualificationTitles extends ListRecords
{
    protected static string $resource = QualificationTitleResource::class;

    protected ?string $heading = 'Qualification Titles';

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
                ->label('New Qualification Titles')
                ->url(route('filament.admin.resources.qualification_titles.create', ['scholarshipProgram_id' => $scholarshipProgramId]))
        ];
    }

    protected function getscholarshipProgramId(): ?int
    {
        return (int) request()->route('record');
    }
}
