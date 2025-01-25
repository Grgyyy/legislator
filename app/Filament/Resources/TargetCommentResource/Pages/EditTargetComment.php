<?php

namespace App\Filament\Resources\TargetCommentResource\Pages;

use App\Filament\Resources\TargetCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTargetComment extends EditRecord
{
    protected static string $resource = TargetCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        $targetId = $this->record->target_id;

        if ($targetId) {
            return route('filament.admin.resources.targets.showHistory', ['record' => $targetId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
