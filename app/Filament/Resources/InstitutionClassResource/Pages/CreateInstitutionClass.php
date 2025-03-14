<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Filament\Resources\InstitutionClassResource;
use App\Helpers\Helper;
use App\Models\InstitutionClass;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateInstitutionClass extends CreateRecord
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-classes-b' => 'Institution Classes',
            'Create'
        ];
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

    protected function handleRecordCreation(array $data): InstitutionClass
    {
        $this->validateUniqueInstitutionClass($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $institutionClass = DB::transaction(fn() => InstitutionClass::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Institution class has been created successfully.');

        return $institutionClass;
    }

    protected function validateUniqueInstitutionClass($data)
    {
        $institutionClass = InstitutionClass::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->first();

        if ($institutionClass) {
            $message = $institutionClass->deleted_at
                ? 'An institution class with this name has been deleted and must be restored before reuse.'
                : 'An institution class with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}