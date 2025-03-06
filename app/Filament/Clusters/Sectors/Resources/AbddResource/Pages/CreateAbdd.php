<?php

namespace App\Filament\Clusters\Sectors\Resources\AbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\AbddResource;
use App\Helpers\Helper;
use App\Models\Abdd;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAbdd extends CreateRecord
{
    protected static string $resource = AbddResource::class;

    protected static ?string $title = 'Create ABDD Sectors';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/abdds' => 'ABDD Sectors',
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

    protected function handleRecordCreation(array $data): Abdd
    {
        $this->validateUniqueAbdd($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $abdd = DB::transaction(fn() => Abdd::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'ABDD sector has been created successfully.');

        return $abdd;
    }

    protected function validateUniqueAbdd($data)
    {
        $abdd = Abdd::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->first();

        if ($abdd) {
            $message = $abdd->deleted_at
                ? 'An ABDD sector with this name has been deleted and must be restored before reuse.'
                : 'An ABDD sector with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}