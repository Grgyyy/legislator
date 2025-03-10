<?php

namespace App\Filament\Clusters\Sectors\Resources\TvetResource\Pages;

use App\Filament\Clusters\Sectors\Resources\TvetResource;
use App\Helpers\Helper;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTvet extends CreateRecord
{
    protected static string $resource = TvetResource::class;

    protected static ?string $title = 'Create TVET Sectors';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/tvets' => 'TVET Sectors',
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

    protected function handleRecordCreation(array $data): Tvet
    {
        $this->validateUniqueTvet($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $tvet = DB::transaction(fn() => Tvet::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'TVET sector has been created successfully.');

        return $tvet;
    }

    protected function validateUniqueTvet($data)
    {
        $tvet = Tvet::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->first();

        if ($tvet) {
            $message = $tvet->deleted_at
                ? 'A TVET sector with this name has been deleted and must be restored before reuse.'
                : 'A TVET sector with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}