<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use App\Helpers\Helper;
use App\Models\Region;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function validateUniqueRegion($data)
    {
        $region = Region::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($region) {
            $message = $region->deleted_at 
                ? 'This region has been deleted and must be restored before reuse.' 
                : 'A region with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = Region::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A region with this PSG code already exists and has been deleted.' 
                    : 'A region with this PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}