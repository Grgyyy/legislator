<?php
namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Filament\Resources\MunicipalityResource;
use App\Helpers\Helper;
use App\Models\District;
use App\Models\Municipality;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateMunicipality extends CreateRecord
{
    protected static string $resource = MunicipalityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
    
    protected function handleRecordCreation(array $data): Municipality
    {
        $this->validateUniqueMunicipality($data);

        $data['name'] = Helper::capitalizeWords($data['name']);
        $data['class'] = Helper::capitalizeWords($data['class']);

        $municipality = DB::transaction(function () use ($data) {
            return Municipality::create([
                'name' => $data['name'],
                'class' => $data['class'],
                'code' => $data['code'],
                'province_id' => $data['province_id'],
            ]);
        });

        if (!empty($data['district_id'])) {
            $district = District::find($data['district_id']);
            if ($district) {
                $district->municipality()->attach($municipality->id);
            }
        }

        NotificationHandler::sendSuccessNotification('Created', 'Municipality has been created successfully.');

        return $municipality;
    }

    protected function validateUniqueMunicipality($data)
    {
        $municipality = Municipality::withTrashed()
            ->where('name', $data['name'])
            ->where('province_id', $data['province_id'])
            ->first();

        if ($municipality) {
            $message = $municipality->deleted_at
                ? 'This municipality exists in the province but has been deleted; it must be restored before reuse.'
                : 'A municipality with this name already exists in the specified province.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = Municipality::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A municipality with this PSG code already exists and has been deleted.' 
                    : 'A municipality with this PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}