<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Models\TviClass;
use App\Filament\Resources\TviClassResource;
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
            '/tvi-classes' => 'Institution Classes',
            'Create'
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): TviClass
    {
        $this->validateUniqueTviClass($data['name']);

        $tviClass = DB::transaction(fn() => TviClass::create([
            'name' => $data['name'],
            'tvi_type_id' => $data['tvi_type_id'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Institution type has been created successfully.');

        return $tviClass;
    }

    protected function validateUniqueTviClass($name)
    {
        $tviClass = TviClass::withTrashed()
            ->where('name', $name)
            ->first();

        if ($tviClass) {
            $message = $tviClass->deleted_at 
                ? 'This institution class has been deleted and must be restored before reuse.' 
                : 'An institution class with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}