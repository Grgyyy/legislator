<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Models\QualificationTitle;
use App\Filament\Resources\QualificationTitleResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class EditQualificationTitle extends EditRecord
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): QualificationTitle
    {
        // Validate unique QualificationTitle before updating
        $this->validateUniqueQualificationTitle($data['training_program_id'], $data['scholarship_program_id'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Qualification Title updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the Qualification Title: ' . $e->getMessage())
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

    protected function validateUniqueQualificationTitle($trainingProgramId, $scholarshipProgramId, $currentId)
    {
        $query = QualificationTitle::withTrashed()
            ->where('training_program_id', $trainingProgramId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            $message = $query->deleted_at
                ? 'A Qualification Title with this combination exists and is marked as deleted. Data cannot be updated.'
                : 'A Qualification Title with this combination already exists.';

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
            'training_program_id' => $message,
            'scholarship_program_id' => $message,
        ]);
    }
}
