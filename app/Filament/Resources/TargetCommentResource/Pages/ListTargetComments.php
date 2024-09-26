<?php

namespace App\Filament\Resources\TargetCommentResource\Pages;

use App\Filament\Resources\TargetCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTargetComments extends ListRecords
{
    protected static string $resource = TargetCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
