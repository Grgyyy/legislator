<?php
namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Models\Municipality;
use App\Services\NotificationHandler;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\DistrictResource;
use App\Helpers\Helper;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

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

    protected function handleRecordCreation(array $data): District
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueDistrict($data);

            $data['name'] = Helper::capitalizeWords($data['name']);

            $district = District::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'municipality_id' => $data['municipality_id'] ?? null,
                'province_id' => $data['province_id'],
            ]);

            if (!empty($data['municipality_id'])) {
                $municipality = Municipality::find($data['municipality_id']);
                $district->municipality()->attach($municipality->id);
            }

            NotificationHandler::sendSuccessNotification('Created', 'District has been created successfully.');

            return $district;
        });
    }

    protected function validateUniqueDistrict($data)
    {
        $districtQuery = District::withTrashed()
            ->where('name', $data['name'])
            ->where('province_id', $data['province_id']);

        if (!empty($data['municipality_id'])) {
            $districtQuery->where('municipality_id', $data['municipality_id']);
        }

        $district = $districtQuery->first();

        if ($district) {
            if (!empty($data['municipality_id'])) {
                $message = $district->deleted_at
                    ? 'This district exists in the municipality but has been deleted; it must be restored before reuse.'
                    : 'A district with this name already exists in the specified municipality.';
            } else {
                $message = $district->deleted_at
                    ? 'This district exists in the province but has been deleted; it must be restored before reuse.'
                    : 'A district with this name already exists in the specified province.';
            }

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = District::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A district with this PSG code already exists and has been deleted.' 
                    : 'A district with this PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}