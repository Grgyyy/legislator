<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\Tvi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ShowInstitutionProgram extends ListRecords
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = "Institution Qualification Titles";

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
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
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


    protected function getTvi(): ?Tvi
    {
        return Tvi::find($this->getTviId());
    }


    protected function getTviId(): ?int
    {
        return (int) request()->route('record');
    }
}
