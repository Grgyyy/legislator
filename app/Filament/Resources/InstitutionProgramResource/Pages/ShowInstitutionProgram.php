<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\Tvi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ShowInstitutionProgram extends ListRecords
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = "Institution Qualification Titles";

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        $tviId = $this->getTviId();

        $tvi = Tvi::find($tviId);

        return [
            route('filament.admin.resources.institution-programs.showPrograms', ['record' => $tvi->id]) => $tvi ? $tvi->name : 'TVI',
            'Institution Qualification Titles',
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
        $tviId = $this->getTviId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.institution-programs.create', ['tvi_id' => $tviId])),
        ];
    }

    protected function getTableQuery(): Builder|null
    {
        $tviId = $this->getTviId();

        return parent::getTableQuery()->where('tvi_id', $tviId);
    }

    protected function getTviId(): ?int
    {
        $tviId = request()->route('record') ?? session('tvi_id');

        if ($tviId) {
            session(['tvi_id' => $tviId]);
        }

        return (int) $tviId;
    }
}
