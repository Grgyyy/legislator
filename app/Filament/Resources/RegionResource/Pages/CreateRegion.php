<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Models\Region;
use App\Filament\Resources\RegionResource;
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

    protected function handleRecordCreation(array $data): Region
    {
        $this->validateUniqueRegion($data['name']);

        $region = DB::transaction(fn() => Region::create([
            'name' => $data['name'],
            'code' => $data['code']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Region has been created successfully.');

        return $region;
    }

    protected function validateUniqueRegion($name)
    {
        $region = Region::withTrashed()
            ->where('name', $name)
            ->first();

        if ($region) {
            $message = $region->deleted_at 
                ? 'This region has been deleted and must be restored before reuse.' 
                : 'A region with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}