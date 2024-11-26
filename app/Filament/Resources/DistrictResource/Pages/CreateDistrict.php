<?php
namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Models\Municipality;
use App\Services\NotificationHandler;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\DistrictResource;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): District
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueDistrict($data['name'], $data['province_id'], $data['code']);

            $district = District::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'province_id' => $data['province_id'],
                'municipality_id' => $data['municipality_id'] ?? null, // Allow null for municipality_id
            ]);

            if (!empty($data['municipality_id'])) {
                $municipality = Municipality::find($data['municipality_id']);
                $district->municipality()->attach($municipality->id);
            }

            Notification::make()
                ->title('Success')
                ->body('District has been created successfully.')
                ->success()
                ->send();

            return $district;
        });
    }

    protected function validateUniqueDistrict($name, $provinceId, $code)
    {
        $district = District::withTrashed()
            ->where('name', $name)
            ->where('code', $code)
            ->where('province_id', $provinceId)
            ->first();

        if ($district) {
            $message = $district->deleted_at
                ? 'This district exists but has been deleted; it must be restored before reuse.'
                : 'A district with this name already exists in the specified province.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
