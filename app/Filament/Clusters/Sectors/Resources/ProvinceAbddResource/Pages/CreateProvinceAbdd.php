<?php

namespace App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource;
use App\Models\ProvinceAbdd;
use App\Services\NotificationHandler;
use DB;
use Filament\Resources\Pages\CreateRecord;

class CreateProvinceAbdd extends CreateRecord
{
    protected static string $resource = ProvinceAbddResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): ProvinceAbdd
    {
        $abddSlots = DB::transaction(fn() => ProvinceAbdd::create([
            'province_id' => $data['province_id'],
            'abdd_id' => $data['abdd_id'],
            'available_slots' => $data['total_slots'],
            'total_slots' => $data['total_slots'],
            'year' => $data['year'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Legislator has been created successfully.');

        return $abddSlots;
    }
}
