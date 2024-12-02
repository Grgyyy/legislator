<?php

namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use App\Filament\Resources\InstitutionRecognitionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInstitutionRecognition extends CreateRecord
{
    protected static string $resource = InstitutionRecognitionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        $this->data['record_id'] = $record->tvi_id;

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        $recordId = $this->data['record_id'] ?? null;

        return route('filament.admin.resources.institution-recognitions.showRecognition', ['record' => $recordId]);
    }
}
