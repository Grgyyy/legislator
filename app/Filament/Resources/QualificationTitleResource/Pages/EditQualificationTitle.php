<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Models\QualificationTitle;
use App\Filament\Resources\QualificationTitleResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditQualificationTitle extends EditRecord
{
    protected static string $resource = QualificationTitleResource::class;

    protected static ?string $title = 'Schedule of Cost';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/schedule-of-cost' => 'Schedule of Cost',
            'Create',
        ];
    }

    protected function handleRecordUpdate($record, array $data): QualificationTitle
    {
        $this->validateUniqueQualificationTitle($data, $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Qualification title has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the schedule of cost: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the schedule of cost update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueQualificationTitle($data, $currentId)
    {
        $qualificationTitle = QualificationTitle::withTrashed()
            ->where('training_program_id', $data['training_program_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($qualificationTitle) {
            $message = $qualificationTitle->deleted_at
                ? 'A schedule of cost associated with the qualification title and scholarship program has been deleted and must be restored before reuse.'
                : 'A schedule of cost associated with the qualification title and scholarship program already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}