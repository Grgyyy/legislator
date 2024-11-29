<?php

namespace App\Filament\Resources\RecognitionResource\Pages;

use App\Filament\Resources\RecognitionResource;
use App\Models\Recognition;
use App\Services\NotificationHandler;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRecognition extends CreateRecord
{
    protected static string $resource = RecognitionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Recognition
    {
        $this->validateUniqueAbdd($data['name']);

        $abdd = DB::transaction(fn () => Recognition::create([
                'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'ABDD sector has been created successfully.');

        return $abdd;
    }

    protected function validateUniqueAbdd($name)
    {
        $abdd = Recognition::withTrashed()
            ->where('name', $name)
            ->first();

        if ($abdd) {
            $message = $abdd->deleted_at 
                ? 'This Recognition Title has been deleted and must be restored before reuse.'
                : 'An Recognition Title with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
