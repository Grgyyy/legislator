<?php

namespace App\Filament\Clusters\Sectors\Resources\PriorityResource\Pages;

use App\Models\Priority;
use App\Filament\Clusters\Sectors\Resources\PriorityResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePriority extends CreateRecord
{
    protected static string $resource = PriorityResource::class;

    protected static ?string $title = 'Create Priority Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/priorities' => 'Top Ten Priority Sectors',
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
                ? 'This priority sector has been deleted and must be restored before reuse.'
                : 'A priority sector with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}