<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Models\InstitutionClass;
use App\Filament\Resources\InstitutionClassResource;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateInstitutionClass extends CreateRecord
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function handleRecordCreation(array $data): InstitutionClass
    {
        $this->validateUniqueInstitutionClass($data['name']);

        $institutionClass = DB::transaction(fn() => InstitutionClass::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Institution class has been created successfully.');

        return $institutionClass;
    }

    protected function validateUniqueInstitutionClass($name)
    {
        $institutionClass = InstitutionClass::withTrashed()
            ->where('name', $name)
            ->first();

        if ($institutionClass) {
            $message = $institutionClass->deleted_at 
                ? 'This institution class has been deleted and must be restored before reuse.' 
                : 'An institution class with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}