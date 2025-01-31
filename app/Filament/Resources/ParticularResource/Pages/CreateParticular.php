<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Filament\Resources\ParticularResource;
use App\Models\District;
use App\Models\Particular;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\Region;
use App\Models\SubParticular;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateParticular extends CreateRecord
{
    protected static string $resource = ParticularResource::class;

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
    
    protected function handleRecordCreation(array $data): Particular
    {
        return DB::transaction(function () use ($data) {
            try {
                $subParticular = SubParticular::find($data['sub_particular_id']);
                if (!$subParticular) {
                    NotificationHandler::handleValidationException('Unexpected Error', 'Particular type does not exist.');
                }

                $partylist = Partylist::where('name', 'Not Applicable')->first();
                if (!$partylist) {
                    NotificationHandler::handleValidationException('Unexpected Error', 'Party-list does not exist.');
                }

                $region = Region::where('name', 'Not Applicable')->first();
                if (!$region) {
                    NotificationHandler::handleValidationException('Unexpected Error', 'Region does not exist.');
                }

                $province = Province::where('name', 'Not Applicable')
                    ->where('region_id', $region->id)
                    ->first();
                if (!$province) {
                    NotificationHandler::handleValidationException('Unexpected Error', 'Province does not exist.');
                }

                $district = District::where('name', 'Not Applicable')
                    ->where('province_id', $province->id)
                    ->first();
                if (!$district) {
                    NotificationHandler::handleValidationException('Unexpected Error', 'District does not exist.');
                }

                $partylistId = ($subParticular->name === 'Party-list' || $subParticular->fundSource->name === 'Party-list')
                    ? $data['administrative_area']
                    : $partylist->id;

                $districtId = ($subParticular->name === 'Party-list' || $subParticular->fundSource->name === 'Party-list')
                    ? $district->id
                    : $data['administrative_area'];

                $this->validateUniqueParticular($data['sub_particular_id'], $partylistId, $districtId);

                $particularData = [
                    'sub_particular_id' => $data['sub_particular_id'],
                    'partylist_id' => $partylistId,
                    'district_id' => $districtId,
                ];

                $particular = Particular::create($particularData);

                NotificationHandler::sendSuccessNotification('Success', 'Particular has been created successfully.');

                return $particular;
            } catch (Exception $e) {
                NotificationHandler::sendErrorNotification('Error', $e->getMessage());

                throw ValidationException::withMessages([
                    'general' => $e->getMessage(),
                ]);
            }
        });
    }

    protected function validateUniqueParticular($sub_particular_id, $partylist_id, $district_id)
    {
        $existingParticular = Particular::withTrashed()
            ->where('sub_particular_id', $sub_particular_id)
            ->where('partylist_id', $partylist_id)
            ->where('district_id', $district_id)
            ->first();

        if ($existingParticular) {
            $message = $existingParticular->deleted_at 
                ? 'A particular with the specified type, party-list, and district has been deleted and must be restored before reuse.' 
                : 'A particular with the specified type, party-list, and district already exists.';

            NotificationHandler::handleValidationException('Validation Error', $message);
        }
    }
}