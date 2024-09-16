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

            $this->validateUniqueTrainingProgram(
                $data['code'],
                $data['title'],
                $data['tvet_id'],
                $data['priority_id']
            );


            return TrainingProgram::create([
                'code' => $data['code'],
                'title' => $data['title'],
                'tvet_id' => $data['tvet_id'],
                'priority_id' => $data['priority_id'],
            ]);
        });
    }


    protected function validateUniqueTrainingProgram($code, $title, $tvet_id, $priority_id)
    {
        $existingProgram = TrainingProgram::withTrashed()
            ->where('code', $code)
            ->where('title', $title)
            ->where('tvet_id', $tvet_id)
            ->where('priority_id', $priority_id)
            ->first();

        if ($existingProgram) {

            $message = $existingProgram->deleted_at
                ? 'A Training Program with this code and title exists and is marked as deleted. You cannot create it again.'
                : 'A Training Program with this code and title already exists.';

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
            'code' => [$message],
        ]);
    }
}
