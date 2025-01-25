<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Models\TviType;
use App\Filament\Resources\TviTypeResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTviType extends CreateRecord
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Create Institution Type';

    public function getBreadcrumbs(): array
    {
        return [
            '/tvi-types' => 'Institution Types',
            'Create',
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
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): TviType
    {
        $this->validateUniqueTviType($data['name']);

        $tviType = DB::transaction(fn() => TviType::create([
            'name' => $data['name']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Institution type has been created successfully.');

        return $tviType;
    }

    protected function validateUniqueTviType($name)
    {
        $tviType = TviType::withTrashed()
            ->where('name', $name)
            ->first();

        if ($tviType) {
            $message = $tviType->deleted_at 
                ? 'This institution type has been deleted and must be restored before reuse.' 
                : 'An institution type with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}