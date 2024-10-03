<?php

namespace App\Filament\Resources\NonCompliantRemarkResource\Pages;

use App\Filament\Resources\NonCompliantRemarkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNonCompliantRemark extends EditRecord
{
    protected static string $resource = NonCompliantRemarkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
