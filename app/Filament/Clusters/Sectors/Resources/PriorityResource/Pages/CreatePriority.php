<?php

namespace App\Filament\Clusters\Sectors\Resources\PriorityResource\Pages;

use App\Filament\Clusters\Sectors\Resources\PriorityResource;
use App\Helpers\Helper;
use App\Models\Priority;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePriority extends CreateRecord
{
    protected static string $resource = PriorityResource::class;

    protected static ?string $title = 'Create Priority Sectors';

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
            '/sectors/priorities' => 'Top Ten Priority Sectors',
            'Create'
        ];
    }

    protected function handleRecordCreation(array $data): Priority
    {
        $this->validateUniquePriority($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $priority = DB::transaction(fn () => Priority::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Priority sector has been created successfully.');

        return $priority;
    }

    protected function validateUniquePriority($data)
    {
        $priority = Priority::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($priority) {
            $message = $priority->deleted_at 
                ? 'A priority sector with this name has been deleted and must be restored before reuse.'
                : 'A priority sector with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}