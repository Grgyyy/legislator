<?php

namespace App\Filament\Resources\LearningModeResource\Pages;

use App\Filament\Resources\LearningModeResource;
use App\Helpers\Helper;
use App\Models\LearningMode;
use App\Models\DeliveryMode;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateLearningMode extends CreateRecord
{
    protected static string $resource = LearningModeResource::class;

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

    protected function handleRecordCreation(array $data): LearningMode
    {
        // Validate uniqueness
        $this->validateUniqueLearningMode($data);

        // Capitalize name
        $data['name'] = Helper::capitalizeWords($data['name']);

        // Create Learning Mode inside a transaction
        $learningMode = DB::transaction(fn() => LearningMode::create([
            'name' => $data['name'],
        ]));

        // Attach delivery modes (Many-to-Many)
        if (!empty($data['delivery_mode_id'])) {
            $deliveryModes = DeliveryMode::whereIn('id', $data['delivery_mode_id'])->get();
            $learningMode->deliveryMode()->attach($deliveryModes->pluck('id')->toArray());
        }

        NotificationHandler::sendSuccessNotification('Created', 'Learning Mode has been created successfully.');

        return $learningMode;
    }

    protected function validateUniqueLearningMode($data)
    {
        $learningMode = LearningMode::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($learningMode) {
            $message = $learningMode->deleted_at
                ? 'A Learning Mode with this name has been deleted and must be restored before reuse.'
                : 'A Learning Mode with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        // Validate delivery modes exist
        if (!empty($data['delivery_mode_id'])) {
            $existingDeliveryModes = DeliveryMode::whereIn('id', $data['delivery_mode_id'])->pluck('id')->toArray();
            $missingModes = array_diff($data['delivery_mode_id'], $existingDeliveryModes);

            if (!empty($missingModes)) {
                NotificationHandler::handleValidationException('Invalid Delivery Mode', 'One or more selected delivery modes do not exist.');
            }
        }
    }
}
