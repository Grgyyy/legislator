<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use App\Helpers\Helper;
use App\Models\Region;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;

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

    protected function handleRecordCreation(array $data): Region
    {
        $this->validateUniqueRegion($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $region = DB::transaction(fn() => Region::create([
            'name' => $data['name'],
            'code' => $data['code']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Region has been created successfully.');

        return $region;
    }

    protected function validateUniqueRegion($data)
    {
        $name = trim($data['name']);

        $region = Region::withTrashed()
            ->whereRaw('TRIM(name) = ?', $name)
            ->first();

        if ($region) {
            $message = $region->deleted_at 
                ? 'A region with this name has been deleted and must be restored before reuse.' 
                : 'A region with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = Region::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A region with the provided PSG code already exists and has been deleted.' 
                    : 'A region with the provided PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}