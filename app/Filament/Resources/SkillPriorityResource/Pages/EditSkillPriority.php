<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use App\Filament\Resources\SkillPriorityResource;
use App\Models\SkillPriority;
use App\Services\NotificationHandler;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditSkillPriority extends EditRecord
{
    protected static string $resource = SkillPriorityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function isEdit(): bool
    {
        return true; // Edit mode
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle record update logic with validation.
     *
     * @param \Illuminate\Database\Eloquent\Model $record
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $this->validateUpdateData($data);

        return DB::transaction(function () use ($record, $data) {
            $existingRecord = SkillPriority::where('province_id', $data['province_id'])
                ->where('training_program_id', $data['training_program_id'])
                ->where('year', $data['year'])
                ->where('id', '!=', $record->id)
                ->first();

            if ($existingRecord) {
                NotificationHandler::sendErrorNotification('Record Exists', 'A record for this Province, Training Program, and Year already exists.');
                return $record;
            }

            $difference = $data['total_slots'] - $record['total_slots'];
            $new_available_slots = $record['available_slots'] + $difference;

            $record->update([
                'province_id' => $data['province_id'],
                'training_program_id' => $data['training_program_id'],
                'available_slots' => $new_available_slots,
                'total_slots' => $data['total_slots'],
                'year' => $data['year'],
            ]);

            NotificationHandler::sendSuccessNotification('Updated', 'Skill Priority has been updated successfully.');

            return $record;
        });
    }

    /**
     * Validate the update data
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateUpdateData(array $data): void
    {
        $validator = \Validator::make($data, [
            'province_id' => ['required', 'integer'],
            'training_program_id' => ['required', 'integer'],
            'year' => ['required', 'numeric', 'min:' . date('Y')],
            'total_slots' => ['required', 'numeric'],
            'available_slots' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            NotificationHandler::sendErrorNotification('Validation Error', 'There was an issue with the provided data.');
            throw new ValidationException($validator);
        }
    }
}
