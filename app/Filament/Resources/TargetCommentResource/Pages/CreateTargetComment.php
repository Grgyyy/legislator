<?php
namespace App\Filament\Resources\TargetCommentResource\Pages;

use App\Filament\Resources\TargetCommentResource;
use App\Models\TargetComment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateTargetComment extends CreateRecord
{
    protected static string $resource = TargetCommentResource::class;

    protected function getRedirectUrl(): string
    {
        $targetId = $this->record->target_id;

        if ($targetId) {
            return route('filament.admin.resources.targets.showComments', ['record' => $targetId]);
        }

        return $this->getResourcep()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function handleRecordCreation(array $data): TargetComment
    {
        return DB::transaction(function () use ($data) {
            // Get the target_id from the query parameter
            // $targetId = request()->query('record');

            // // Debugging: Log the retrieved target ID
            // \Log::info('Retrieved target ID from query:', ['target_id' => $targetId]);

            // // Ensure target_id is not null
            // if (is_null($targetId)) {
            //     throw new \Exception('Target ID cannot be null');
            // }

            // Create the TargetComment with the target_id from the URL
            $targetComment = TargetComment::create([
                'target_id' => $data['target_id'],
                'user_id' => Auth::id(),
                'content' => $data['content'],
            ]);

            return $targetComment;
        });
    }

}
