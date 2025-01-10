<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Models\TrainingProgram;
use App\Filament\Resources\TrainingProgramResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTrainingProgram extends CreateRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): TrainingProgram
    {
        $this->validateUniqueTrainingProgram($data);

        $trainingProgram = DB::transaction(fn () => TrainingProgram::create([
                'code' => $data['code'],
                'soc_code' => $data['soc_code'],
                'title' => $data['title'],
                'priority_id' => $data['priority_id'],
                'tvet_id' => $data['tvet_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Training program has been created successfully.');

        return $trainingProgram;
    }

    protected function validateUniqueTrainingProgram($data)
    {
        $trainingProgram = TrainingProgram::withTrashed()
            ->where('code', $data['code'])
            ->where('soc_code', $data['soc_code'])
            ->where(DB::raw('LOWER(title)'), strtolower($data['title']))
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at
                ? 'This training program with the provided details has been deleted and must be restored before reuse.'
                : 'A training program with the provided details already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
