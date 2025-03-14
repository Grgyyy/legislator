<?php

namespace App\Filament\Resources\RecognitionResource\Pages;

use App\Filament\Resources\RecognitionResource;
use App\Helpers\Helper;
use App\Models\Recognition;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateRecognition extends CreateRecord
{
    protected static string $resource = RecognitionResource::class;

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

    protected function handleRecordCreation(array $data): Recognition
    {
        $this->validateUniqueAbdd($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $recognition = DB::transaction(fn() => Recognition::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Recognition has been created successfully.');

        return $recognition;
    }

    protected function validateUniqueAbdd($data)
    {
        $recognition = Recognition::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->first();

        if ($recognition) {
            $message = $recognition->deleted_at
                ? 'A recognition title with this name has been deleted and must be restored before reuse.'
                : 'A recognition title with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
