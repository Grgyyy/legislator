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

    protected function handleRecordCreation(array $data): TrainingProgram
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueTrainingProgram($data['code'], $data['title']);

            return TrainingProgram::create([
                'code' => $data['code'],
                'title' => $data['title'],
            ]);
        });
    }

    protected function validateUniqueTrainingProgram($code, $title)
    {
        $existingProgram = TrainingProgram::withTrashed()
            ->where('code', $code)
            ->where('title', $title)
            ->first();

        if ($existingProgram) {
            $message = $existingProgram->deleted_at
                ? 'A Training Program with this code exists and is marked as deleted. Data cannot be created.'
                : 'A Training Program with this code already exists.';

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
