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
        // Call the parent method to create the record
        $record = parent::handleRecordCreation($data);

        // Store the created record's ID in the data property for redirection
        $this->data['record_id'] = $record->tvi_id;

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        // Retrieve the record ID from the data property
        $recordId = $this->data['record_id'] ?? null;

        // Redirect to the custom route with the record ID
        return route('filament.admin.resources.institution-recognitions.showRecognition', ['record' => $recordId]);
    }
}
