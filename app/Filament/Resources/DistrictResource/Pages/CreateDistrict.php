<?php
namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use App\Helpers\Helper;
use App\Models\District;
use App\Models\Municipality;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

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
        try {
            $this->validateUniqueDistrict($data);
    
            $data['name'] = Helper::capitalizeWords($data['name']);
    
            return DB::transaction(function () use ($data) {
                if ($data['huc']) {
                    $district = District::create([
                        'name' => $data['name'],
                        'code' => $data['code'],
                        'municipality_id' => $data['municipality_id'],
                        'province_id' => $data['province_id']
                    ]);
    
                    if ($municipality = Municipality::find($data['municipality_id'])) {
                        $district->municipality()->attach($municipality->id);
                    }
                } else {
                    $district = District::create([
                        'name' => $data['name'],
                        'code' => $data['code'],
                        'municipality_id' => null,
                        'province_id' => $data['province_id']
                    ]);
                }
    
                NotificationHandler::sendSuccessNotification('Created', 'District has been created successfully.');
    
                return $district;
            });
        } catch (\Exception $e) {   
            NotificationHandler::handleValidationException('Error', 'Failed to create district. Please try again.');
        }
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
                    ? 'A district with this name already exists in the municipality but has been deleted; it must be restored before reuse.'
                    : 'A district with this name already exists in the specified municipality.';
            } else {
                $message = $district->deleted_at
                    ? 'A district with this name already exists in the province but has been deleted; it must be restored before reuse.'
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
                    ? 'A district with the provided PSG code already exists and has been deleted.' 
                    : 'A district with the provided PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}