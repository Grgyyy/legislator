<?php

namespace App\Filament\Resources\TrainingProgramResource\Pages;

use App\Models\TrainingProgram;
use App\Filament\Resources\TrainingProgramResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTrainingProgram extends CreateRecord
{
    protected static string $resource = TrainingProgramResource::class;

    protected ?string $heading = 'Create Qualification Title';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/qualification-titles'=> 'Qualification Titles',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): TrainingProgram
    {
        $this->validateUniqueTrainingProgram($data);

        $data['title'] = Helper::capitalizeWords($data['title']);

        $trainingProgram = DB::transaction(fn () => TrainingProgram::create([
                'code' => $data['code'],
                'soc_code' => $data['soc_code'],
                'title' => $data['title'],
                'full_coc_ele' => $data['full_coc_ele'],
                'nc_level' => $data['nc_level'],
                'priority_id' => $data['priority_id'],
                'tvet_id' => $data['tvet_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Qualification title has been created successfully.');

        return $trainingProgram;
    }

    protected function validateUniqueTrainingProgram($data)
    {
        $trainingProgram = TrainingProgram::withTrashed()
            ->where('soc_code', $data['soc_code'])
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at
                ? 'A qualification title with the provided SoC code has been deleted and must be restored before reuse.'
                : 'A qualification title with the provided SoC code already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}