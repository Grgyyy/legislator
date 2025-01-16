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

    public function getHeading(): string
    {
        $record = $this->getRecord();
        return $record ? $record->title : 'Qualification Titles';
    }
    
    public function getBreadcrumbs(): array
    {

        $record = $this->getRecord();

        return [
            route('filament.admin.resources.training-programs.index') => $record ? $record->title : 'Qualification Titles',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): TrainingProgram
    {
        $this->validateUniqueTrainingProgram($data, $record->id);

        try {

            if($data['full_coc_ele'] === 'COC' || $data['full_coc_ele'] === 'ELEV') {
                $data['nc_level'] = null;
            }

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
            ->where('code', $data['code'])
            ->where('soc_code', $data['soc_code'])
            ->where(DB::raw('LOWER(title)'), strtolower($data['title']))
            ->whereNot('id', $currentId)
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at 
                ? 'This training program with the provided details has been deleted. Restoration is required before it can be reused.' 
                : 'A training program with the provided details already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
