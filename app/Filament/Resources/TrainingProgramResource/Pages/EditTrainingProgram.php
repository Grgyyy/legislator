<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Models\TrainingProgram;
use App\Filament\Resources\TrainingProgramResource;
use App\Services\NotificationHandler;
use DB;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditTrainingProgram extends EditRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): TrainingProgram
    {
        $this->validateUniqueTrainingProgram($data, $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Training program has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the training program: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the training program update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueTrainingProgram(array $data, $currentId)
    {
        $trainingProgram = TrainingProgram::withTrashed()
            ->where(DB::raw('LOWER(title)'), strtolower($data['title']))
            ->where('tvet_id', $data['tvet_id'])
            ->where('priority_id', $data['priority_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at 
                ? 'This training program with the provided details has been deleted. Restoration is required before it can be reused.' 
                : 'A training program with the provided details already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $code = TrainingProgram::withTrashed()
            ->where('code', $data['code'])
            ->whereNot('id', $currentId)
            ->first();

        if ($code) {
            $message = $code->deleted_at 
                ? 'A training program with this code already exists and has been deleted.' 
                : 'A training program with this code already exists.';
            
            NotificationHandler::handleValidationException('Invalid Code', $message);
        }
    }
}
