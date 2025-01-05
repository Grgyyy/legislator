<?php

namespace App\Filament\Resources\InstitutionProgramResource\Pages;

use App\Filament\Resources\InstitutionProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstitutionPrograms extends ListRecords
{
    protected static string $resource = InstitutionProgramResource::class;

    protected static ?string $title = "Institution's Training Programs";

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-programs' => "Institution's Training Programs",
            'List',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
