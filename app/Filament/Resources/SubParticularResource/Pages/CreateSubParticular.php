<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Filament\Resources\SubParticularResource;
use App\Helpers\Helper;
use App\Models\SubParticular;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSubParticular extends CreateRecord
{
    protected static string $resource = SubParticularResource::class;

    protected static ?string $title = 'Create Particular Type';

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

    public function getBreadcrumbs(): array
    {
        return [
            '/particular-types' => 'Particular Types',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): SubParticular
    {
        $this->validateUniqueSubParticular($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $subParticular = DB::transaction(fn() => SubParticular::create([
            'name' => $data['name'],
            'fund_source_id' => $data['fund_source_id']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Particular type has been created successfully.');

        return $subParticular;
    }

    protected function validateUniqueSubParticular($data)
    {
        $subParticular = SubParticular::withTrashed()
            ->where('name', $data['name'])
            ->where('fund_source_id', $data['fund_source_id'])
            ->first();

        if ($subParticular) {
            $message = $subParticular->deleted_at 
                ? 'A particular type for the selected fund source has been deleted and must be restored before reuse.' 
                : 'A particular type for the selected fund source already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}