<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use App\Filament\Resources\SkillPriorityResource;
use App\Models\SkillPriority;
use App\Services\NotificationHandler;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateSkillPriority extends CreateRecord
{
    protected static string $resource = SkillPriorityResource::class;

    public function isEdit(): bool
    {
        return false; // Edit mode
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
        $this->validateCreateData($data);

        return DB::transaction(function () use ($data) {
            $existingRecord = SkillPriority::where('province_id', $data['province_id'])
                ->where('training_program_id', $data['training_program_id'])
                ->where('year', $data['year'])
                ->first();

            if ($existingRecord) {
                NotificationHandler::sendErrorNotification('Record Exists', 'A record for this Province, ABDD, and Year already exists.');
                return $existingRecord;
            }

            $provinceAbdd = SkillPriority::create([
                'province_id' => $data['province_id'],
                'training_program_id' => $data['training_program_id'],
                'available_slots' => $data['total_slots'],
                'total_slots' => $data['total_slots'],
                'year' => $data['year'],
            ]);

            NotificationHandler::sendSuccessNotification('Created', 'Skill Priority has been created successfully.');

            return $provinceAbdd;
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
        $validator = \Validator::make($data, [
            'province_id' => ['required', 'integer'],
            'training_program_id' => ['required', 'integer'],
            'year' => ['required', 'numeric', 'min:' . date('Y')], // Ensure year is not less than the current year
            'total_slots' => ['required', 'numeric'],
            'available_slots' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            NotificationHandler::sendErrorNotification('Validation Error', 'There was an issue with the provided data.');
            throw new ValidationException($validator);
        }
    }
}
