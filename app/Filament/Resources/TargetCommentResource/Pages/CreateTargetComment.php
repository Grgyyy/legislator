<?php

namespace App\Filament\Resources\TargetCommentResource\Pages;

use App\Filament\Resources\TargetCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTargetComment extends CreateRecord
{
    protected static string $resource = TargetCommentResource::class;

    protected function getRedirectUrl(): string
    {
        $targetId = $this->record->target_id;

        if ($targetId) {
            return route('filament.admin.resources.targets.showHistory', ['record' => $targetId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
