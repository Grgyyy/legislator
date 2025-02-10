<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Filament\Resources\LegislatorResource;
use App\Helpers\Helper;
use App\Models\Legislator;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateLegislator extends CreateRecord
{
    protected static string $resource = LegislatorResource::class;

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

    protected function handleRecordCreation(array $data): Legislator
    {
        $this->validateUniqueLegislator($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $legislator = DB::transaction(fn() => Legislator::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Legislator has been created successfully.');

        return $legislator;
    }

    protected function validateUniqueLegislator($data)
    {
        $legislator = Legislator::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($legislator) {
            $message = $legislator->deleted_at 
                ? 'A legislator with this name has been deleted and must be restored before reuse.'
                : 'A legislator with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}