<?php

namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use App\Filament\Resources\InstitutionRecognitionResource;
use App\Helpers\Helper;
use App\Models\InstitutionRecognition;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateInstitutionRecognition extends CreateRecord
{
    protected static string $resource = InstitutionRecognitionResource::class;
    
    protected static ?string $title = 'Create Institution Qualification Titles';

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

    protected function handleRecordCreation(array $data): Model
    {
        $institutionRecog = DB::transaction(fn() => InstitutionRecognition::create([
            'tvi_id' => $data['tvi_id'],
            'recognition_id' => $data['recognition_id'],
            'accreditation_date' => $data['accreditation_date'],
            'expiration_date' => $data['expiration_date']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Institution Recognition has been created successfully.');

        return $institutionRecog;
    }
}