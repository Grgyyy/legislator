<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use App\Filament\Resources\SkillPriorityResource;
use App\Models\SkillPriority;
use App\Models\Status;
use App\Services\NotificationHandler;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
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
            $status = Status::where('desc', 'Active')->first();

            if (!$status) {
                $this->notifyError('The "Active" status does not exist.');
                return null;
            }

            $existingRecord = SkillPriority::where('province_id', $data['province_id'])
                ->where('qualification_title', $data['qualification_title'])
                ->where('year', $data['year'])
                ->where('status_id', $status->id)
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
                'district_id' => $data['district_id'] ?? null,
                'qualification_title' => $data['qualification_title'],
                'available_slots' => $new_available_slots,
                'total_slots' => $data['total_slots'],
                'year' => $data['year'],
                'status_id' => $status->id,
            ]);

            if (!empty($data['qualification_title_id'])) {
                $record->trainingProgram()->sync($data['qualification_title_id']);
            }

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
        $validator = Validator::make($data, [
            'province_id' => ['required', 'integer'],
            'year' => ['required', 'numeric', 'min:' . date('Y')],
            'total_slots' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            NotificationHandler::sendErrorNotification('Validation Error', 'There was an issue with the provided data.');
            throw new ValidationException($validator);
        }
    }
}
