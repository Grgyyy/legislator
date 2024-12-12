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

    public function isEdit(): bool
    {
        return false;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): ProvinceAbdd
    {
        return DB::transaction(function () use ($data) {
            $existingRecord = ProvinceAbdd::where('province_id', $data['province_id'])
                ->where('abdd_id', $data['abdd_id'])
                ->where('year', $data['year'])
                ->first();

            if ($existingRecord) {
                NotificationHandler::sendErrorNotification('Record Exists', 'A record for this Province, ABDD, and Year already exists.');
                return $existingRecord;
            }

            $provinceAbdd = ProvinceAbdd::create([
                'province_id' => $data['province_id'],
                'abdd_id' => $data['abdd_id'],
                'available_slots' => $data['total_slots'],
                'total_slots' => $data['total_slots'],
                'year' => $data['year'],
            ]);

            NotificationHandler::sendSuccessNotification('Created', 'Province ABDD has been created successfully.');

            return $provinceAbdd;
        });
    }
}
