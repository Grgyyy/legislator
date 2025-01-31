<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Filament\Resources\TrainingProgramResource;
use App\Helpers\Helper;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class EditTrainingProgram extends EditRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected static ?string $title = "Edit Qualification Title";

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
            '/qualification-titles'=> 'Qualification Titles',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): TrainingProgram
    {
        $this->validateUniqueTrainingProgram($data, $record->id);

        $data['title'] = Helper::capitalizeWords($data['title']);

        try {
            if($data['full_coc_ele'] === 'COC' || $data['full_coc_ele'] === 'ELEV') {
                $data['nc_level'] = null;
            }

            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Qualification Title has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the qualification title: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the qualification title update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueTrainingProgram(array $data, $currentId)
    {
        $trainingProgram = TrainingProgram::withTrashed()
            ->where('soc_code', $data['soc_code'])
            ->whereNot('id', $currentId)
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at
                ? 'A qualification title with the provided SoC code has been deleted and must be restored before reuse.'
                : 'A qualification title with the provided SoC code already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $trainingProgram = TrainingProgram::withTrashed()
            ->where('title', $data['title'])
            ->where('tvet_id', $data['tvet_id'])
            ->where('priority_id', $data['priority_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at
                ? 'A qualification title with the provided details has been deleted and must be restored before reuse.'
                : 'A qualification title with the provided details already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}