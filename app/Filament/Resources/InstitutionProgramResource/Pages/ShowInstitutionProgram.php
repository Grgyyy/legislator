<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use App\Models\Tvi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ShowInstitutionProgram extends ListRecords
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = "Institution's Training Programs";


    public function getBreadcrumbs(): array
    {
        $tvi = $this->getTvi();

        return [
            '/institution-programs' => $tvi ? "{$tvi->name} Training Programs" : "Training Programs",
            'List',
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


    protected function getTvi(): ?Tvi
    {
        return Tvi::find($this->getTviId());
    }


    protected function getTviId(): ?int
    {
        return (int) request()->route('record');
    }
}
