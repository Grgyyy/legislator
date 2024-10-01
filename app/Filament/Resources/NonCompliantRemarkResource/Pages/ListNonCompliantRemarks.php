<?php

namespace App\Filament\Resources\NonCompliantRemarkResource\Pages;

use App\Filament\Resources\NonCompliantRemarkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNonCompliantRemarks extends ListRecords
{
    protected static string $resource = NonCompliantRemarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
