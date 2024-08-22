<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Filament\Resources\ScholarshipProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScholarshipPrograms extends ListRecords
{
    protected static string $resource = ScholarshipProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
        ];
    }
}
