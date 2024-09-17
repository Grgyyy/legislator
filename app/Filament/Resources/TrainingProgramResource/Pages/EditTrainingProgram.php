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

    public function getBreadcrumbs(): array
    {
        return [
            'Trainin Program',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): TrainingProgram
    {
        // Validate for unique training program attributes before updating
        $this->validateUniqueTrainingProgram($data, $record->id);

        try {
            // Update the record after successful validation
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

    protected function validateUniqueTrainingProgram(array $data, $currentId)
    {
        $query = TrainingProgram::withTrashed()
            ->where('code', $data['code'])
            ->where('title', $data['title'])
            ->where('tvet_id', $data['tvet_id'])
            ->where('priority_id', $data['priority_id'])
            ->where('id', '!=', $currentId) // Exclude the current record being edited
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'A Training Program with these attributes exists and is marked as deleted. Update cannot be performed.';
            } else {
                $message = 'A Training Program with these attributes already exists.';
            }
            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        // Notify the user of the validation error
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();

        // Throw a validation exception with the custom error message
        throw ValidationException::withMessages([
            'code' => $message,
        ]);
    }
}
