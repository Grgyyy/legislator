<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Filament\Resources\TviClassResource;
use App\Helpers\Helper;
use App\Models\TviClass;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTviClass extends CreateRecord
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Create Institution Class';

    public function getBreadcrumbs(): array
    {
        return [
            '/institution-classes' => 'Institution Classes',
            'Create'
        ];
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): TviClass
    {
        $this->validateUniqueTviClass($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $tviClass = DB::transaction(fn() => TviClass::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Institution class has been created successfully.');

        return $tviClass;
    }

    protected function validateUniqueTviClass($data)
    {
        $tviClass = TviClass::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->first();

        if ($tviClass) {
            $message = $tviClass->deleted_at
                ? 'An institution class with this name has been deleted and must be restored before reuse.'
                : 'An institution class with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}