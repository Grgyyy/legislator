<?php

namespace App\Filament\Resources\SkillPriorityResource\Pages;

use App\Filament\Resources\SkillPriorityResource;
use App\Models\SkillPriority;
use App\Models\Status;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateSkillPriority extends CreateRecord
{
    protected static string $resource = SkillPriorityResource::class;

    public function isEdit(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Save & Exit'),
            $this->getCreateAnotherFormAction()->label('Save & Create Another'),
            $this->getCancelFormAction()->label('Exit'),
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

    protected function handleRecordCreation(array $data): SkillPriority
    {
        $this->validateCreateData($data);

        return DB::transaction(function () use ($data) {
            $status = Status::where('desc', 'Active')->first();

            if (!$status) {
                $this->notifyError('The "Active" status does not exist.');
                return null;
            }

            $existingRecordQuery = SkillPriority::where('province_id', $data['province_id'])
                ->where('qualification_title', $data['qualification_title'])
                ->where('year', $data['year'])
                ->where('status_id', $status->id);

            if (!empty($data['district_id'])) {
                $existingRecordQuery->where('district_id', $data['district_id']);
            }

            $existingRecord = $existingRecordQuery->first();

            if ($existingRecord) {
                $message = 'A record for this Province, District (if selected), Training Program, and Year already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            $skillPriority = SkillPriority::create([
                'province_id' => $data['province_id'],
                'district_id' => $data['district_id'] ?? null,
                'qualification_title' => $data['qualification_title'],
                'available_slots' => $data['total_slots'],
                'total_slots' => $data['total_slots'],
                'year' => $data['year'],
                'status_id' => $status->id,
            ]);

            if (!empty($data['qualification_title_id'])) {
                $skillPriority->trainingProgram()->sync($data['qualification_title_id']);
            }

            NotificationHandler::sendSuccessNotification('Created', 'Skill Priority has been created successfully.');

            return $skillPriority;
        });
    }

    protected function afterCreate(): void
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->record)
            ->event('Created')
            ->withProperties([
                'province' => $this->record->provinces->name,
                'district' => $this->record->district->name ?? null,
                'lot_name' => $this->record->qualification_title,
                'qualification_title' => $this->record->trainingProgram->implode('title', ', '),
                'available_slots' => $this->record->available_slots,
                'total_slots' => $this->record->total_slots,
                'year' => $this->record->year,
                'status' => $this->record->status->desc,
            ])
            ->log("An Skill Priority for '{$this->record->qualification_title}' has been created.");
    }

    protected function validateCreateData(array $data): void
    {
        $validator = Validator::make($data, [
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'year' => ['required', 'numeric', 'min:' . date('Y'), 'digits:4'],
            'total_slots' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            $message = 'Please check the inputted information.';
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
