<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Models\Particular;
use App\Models\SubParticular;
use App\Models\Partylist;
use App\Models\District;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Region;
use App\Filament\Resources\ParticularResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class CreateParticular extends CreateRecord
{
    protected static string $resource = ParticularResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Particular
    {
        $this->validateUniqueParticular($data['sub_particular_id']);

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

                $municipality = Municipality::where('name', 'Not Applicable')
                    ->where('province_id', $province->id)
                    ->first();
                if (!$municipality) {
                    NotificationHandler::handleValidationException('Unexpected Error', 'Municipality does not exist.');
                }

                $district = District::where('name', 'Not Applicable')
                    ->where('municipality_id', $municipality->id)
                    ->first();
                if (!$district) {
                    NotificationHandler::handleValidationException('Unexpected Error', 'District does not exist.');
                }

                $particularData = [
                    'sub_particular_id' => $data['sub_particular_id'],
                    'partylist_id' => $subParticular->name === 'Party-list' ||  $subParticular->fundSource->name === 'Party-list' ? $data['administrative_area'] : $partylist->id,
                    'district_id' => $subParticular->name === 'Party-list' ||  $subParticular->fundSource->name === 'Party-list' ? $district->id : $data['administrative_area'],
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

    protected function validateUniqueParticular($sub_particular_id)
    {
        $particular = Particular::withTrashed()
            ->where('sub_particular_id', $sub_particular_id)
            ->first();

        if ($particular) {
            $message = $particular->deleted_at 
                ? 'This particular has been deleted and must be restored before reuse.' 
                : 'A particular with the specified type and administrative area already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}