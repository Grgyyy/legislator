<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Filament\Resources\ScholarshipProgramResource;
use App\Helpers\Helper;
use App\Models\ScholarshipProgram;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditScholarshipProgram extends EditRecord
{
    protected static string $resource = ScholarshipProgramResource::class;

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

    protected function handleRecordUpdate($record, array $data): ScholarshipProgram
    {
        $this->validateUniqueScholarshipProgram($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);
        $data['desc'] = Helper::capitalizeWords($data['desc']);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Scholarship program has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the scholarship program: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the scholarship program update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueScholarshipProgram($data, $currentId)
    {
        $schoPro = ScholarshipProgram::withTrashed()
            ->where('name', $data['name'])
            ->whereNot('id', $currentId)
            ->first();

        if ($schoPro) {
            $message = $schoPro->deleted_at 
                ? 'A scholarship program with this name has been deleted and must be restored before reuse.' 
                : 'A scholarship program with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $desc = ScholarshipProgram::withTrashed()
            ->where('desc', $data['desc'])
            ->whereNot('id', $currentId)
            ->first();

        if ($desc) {
            $message = $desc->deleted_at
                ? 'A scholarship program with the provided details has been deleted and must be restored before reuse.'
                : 'A scholarship program with the provided details already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}