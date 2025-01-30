<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use App\Models\FundSource;
use App\Filament\Resources\FundSourceResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateFundSource extends CreateRecord
{
    protected static string $resource = FundSourceResource::class;

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

    protected function handleRecordCreation(array $data): FundSource
    {
        $this->validateUniqueFundSource($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $fundSource = DB::transaction(fn() => FundSource::create([
            'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Fund source has been created successfully.');

        return $fundSource;
    }

    protected function validateUniqueFundSource($data)
    {
        $fundSource = FundSource::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($fundSource) {
            $message = $fundSource->deleted_at 
                ? 'A fund source with this name deleted and must be restored before reuse.' 
                : 'A fund source with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}