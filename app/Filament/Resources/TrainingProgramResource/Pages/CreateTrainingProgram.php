<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Models\TrainingProgram;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\TrainingProgramResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTrainingProgram extends CreateRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Handle record creation with custom validation
    protected function handleRecordCreation(array $data): TrainingProgram
    {
        return DB::transaction(function () use ($data) {
            // Validate uniqueness of the training program
            $this->validateUniqueTrainingProgram(
                $data['code'],
                $data['title'],
                $data['tvet_id'],
                $data['priority_id']
            );

            // Proceed to create the training program
            return TrainingProgram::create([
                'code' => $data['code'],
                'title' => $data['title'],
                'tvet_id' => $data['tvet_id'],
                'priority_id' => $data['priority_id'],
            ]);
        });
    }

    // Validate the uniqueness of the training program by checking code, title, tvet_id, and priority_id
    protected function validateUniqueTrainingProgram($code, $title, $tvet_id, $priority_id)
    {
        $existingProgram = TrainingProgram::withTrashed()
            ->where('code', $code)
            ->where('title', $title)
            ->where('tvet_id', $tvet_id)
            ->where('priority_id', $priority_id)
            ->first();

        if ($existingProgram) {
            // Different error messages based on soft-deleted status
            $message = $existingProgram->deleted_at
                ? 'A Training Program with this code and title exists and is marked as deleted. You cannot create it again.'
                : 'A Training Program with this code and title already exists.';

            $this->handleValidationException($message);
        }
    }

    // Handle validation exceptions and notify the user
    protected function handleValidationException($message)
    {
        // Send notification using Filament
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();

        // Throw validation exception with the message
        throw ValidationException::withMessages([
            'code' => [$message],
        ]);
    }
}
