<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Models\TrainingProgram;
use App\Filament\Resources\TrainingProgramResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class EditTrainingProgram extends EditRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): TrainingProgram
    {
        // Validate for unique training program code
        $this->validateUniqueTrainingProgram($data['code'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Training Program updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the training program: ' . $e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An unexpected error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        return $record;
    }

    protected function validateUniqueTrainingProgram($code, $currentId)
    {
        $query = TrainingProgram::withTrashed()
            ->where('code', $code)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Training Program with this code exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Training Program with this code already exists.';
            }
            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'code' => $message,
        ]);
    }
}
