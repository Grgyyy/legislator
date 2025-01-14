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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getHeading(): string
    {
        $record = $this->getRecord();
        return $record ? $record->trainingProgram->title : 'Project Proposal Programs';
    }
    
    public function getBreadcrumbs(): array
    {

        $record = $this->getRecord();

        return [
            route('filament.admin.resources.training-programs.index') => $record ? $record->trainingProgram->title : 'Project Proposal Programs',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): QualificationTitle
    {
        $this->validateUniqueQualificationTitle($data['training_program_id'], $data['scholarship_program_id'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Qualification title has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the qualification title: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the qualification title update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueQualificationTitle($trainingProgramId, $scholarshipProgramId, $currentId)
    {
        $qualificationTitle = QualificationTitle::withTrashed()
            ->where('training_program_id', $trainingProgramId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->whereNot('id', $currentId)
            ->first();

        if ($qualificationTitle) {
            $message = $qualificationTitle->deleted_at
                ? 'This qualification title associated with the training program and scholarship program has been deleted. Restoration is required before it can be reused.'
                : 'A qualification title associated with the training program and scholarship program already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}