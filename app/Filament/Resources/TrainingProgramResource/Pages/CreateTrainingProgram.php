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

    protected ?string $heading = 'Create a Qualification Title';

    public function getBreadcrumbs(): array
    {
        return [
            '/qualification-titles'=> 'Qualification Titles',
            'Create'
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function handleRecordCreation(array $data): TrainingProgram
    {
        $this->validateUniqueTrainingProgram($data);

        $trainingProgram = DB::transaction(fn () => TrainingProgram::create([
                'code' => $data['code'],
                'soc_code' => $data['soc_code'],
                'title' => $data['title'],
                'full_coc_ele' => $data['full_coc_ele'],
                'nc_level' => $data['nc_level'],
                'priority_id' => $data['priority_id'],
                'tvet_id' => $data['tvet_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Training program has been created successfully.');

        return $trainingProgram;
    }

    protected function validateUniqueTrainingProgram($data)
    {
        $trainingProgram = TrainingProgram::withTrashed()
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
