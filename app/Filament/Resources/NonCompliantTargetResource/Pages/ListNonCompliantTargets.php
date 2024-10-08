<?php

namespace App\Filament\Resources\NonCompliantTargetResource\Pages;

use App\Filament\Resources\NonCompliantTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNonCompliantTargets extends ListRecords
{
    protected static string $resource = NonCompliantTargetResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
}
