<?php

namespace App\Filament\Resources\DeliveryModeResource\Pages;

use App\Filament\Resources\DeliveryModeResource;
use App\Helpers\Helper;
use App\Models\DeliveryMode;
use App\Models\LearningMode;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateDeliveryMode extends CreateRecord
{
    protected static string $resource = DeliveryModeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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

    protected function handleRecordCreation(array $data): DeliveryMode
    {
        $this->validateUniqueDeliveryMode($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $deliveryMode = DB::transaction(fn() => DeliveryMode::create([
            'name' => $data['name'],
            'acronym' => $data['acronym'],
        ]));

        if (!empty($data['learning_mode_id'])) {
            $learningModes = LearningMode::whereIn('id', $data['learning_mode_id'])->get();
            $deliveryMode->learningMode()->attach($learningModes->pluck('id')->toArray());
        }

        NotificationHandler::sendSuccessNotification('Created', 'Delivery Mode has been created successfully.');

        return $deliveryMode;
    }

    protected function validateUniqueDeliveryMode($data)
    {
        $deliveryMode = DeliveryMode::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($deliveryMode) {
            $message = $deliveryMode->deleted_at
                ? 'A Delivery Mode with this name has been deleted and must be restored before reuse.'
                : 'A Delivery Mode with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['learning_mode_id'])) {
            $existingLearningModes = LearningMode::whereIn('id', $data['learning_mode_id'])->pluck('id')->toArray();
            $missingModes = array_diff($data['learning_mode_id'], $existingLearningModes);

            if (!empty($missingModes)) {
                NotificationHandler::handleValidationException('Invalid Learning Mode', 'One or more selected learning modes do not exist.');
            }
        }
    }
}
