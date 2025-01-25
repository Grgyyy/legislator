<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use App\Filament\Resources\SkillPriorityResource;
use App\Models\SkillPriority;
use App\Services\NotificationHandler;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateSkillPriority extends CreateRecord
{
    protected static string $resource = SkillPriorityResource::class;

    public function isEdit(): bool
    {
        return false; // Edit mode
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle record creation logic with validation.
     *
     * @param array $data
     * @return SkillPriority
     */
    protected function handleRecordCreation(array $data): SkillPriority
    {
        // Validate input data
        $this->validateCreateData($data);

        return DB::transaction(function () use ($data) {
            $existingRecord = SkillPriority::where('province_id', $data['province_id'])
                ->where('training_program_id', $data['training_program_id'])
                ->where('year', $data['year'])
                ->first();

            if ($existingRecord) {
                NotificationHandler::sendErrorNotification('Record Exists', 'A record for this Province, Training Program, and Year already exists.');
                return $existingRecord; // Returning existing record prevents duplicate entries
            }

            // Create new SkillPriority record
            $skillPriority = SkillPriority::create([
                'province_id' => $data['province_id'],
                'training_program_id' => $data['training_program_id'],
                'available_slots' => $data['total_slots'],
                'total_slots' => $data['total_slots'],
                'year' => $data['year'],
            ]);

            NotificationHandler::sendSuccessNotification('Created', 'Skill Priority has been created successfully.');

            return $skillPriority;
        });
    }

    /**
     * Validate the create data
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateCreateData(array $data): void
    {
        $validator = Validator::make($data, [
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'training_program_id' => ['required', 'integer', 'exists:training_programs,id'],
            'year' => ['required', 'numeric', 'min:' . date('Y'), 'digits:4'],
            'total_slots' => ['required', 'integer', 'min:0'], // Ensure slots are non-negative
        ]);

        if ($validator->fails()) {
            NotificationHandler::sendErrorNotification('Validation Error', 'There was an issue with the provided data.');
            throw new ValidationException($validator);
        }
    }

}
