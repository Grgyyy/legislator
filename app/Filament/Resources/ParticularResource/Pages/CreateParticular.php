<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Models\District;
use App\Models\Municipality;
use App\Models\Particular;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\Region;
use App\Models\SubParticular;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\ParticularResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
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
        return DB::transaction(function () use ($data) {
            try {
                $subParticular = SubParticular::find($data['sub_particular_id']);
                if (!$subParticular) {
                    throw new Exception('SubParticular not found.');
                }

                $notApplicablePartylist = Partylist::where('name', 'Not Applicable')->first();
                if (!$notApplicablePartylist) {
                    throw new Exception('Not Applicable Partylist not found.');
                }

                $region = Region::where('name', 'Not Applicable')->first();
                if (!$region) {
                    throw new Exception('Region not found.');
                }

                $province = Province::where('name', 'Not Applicable')
                    ->where('region_id', $region->id)
                    ->first();
                if (!$province) {
                    throw new Exception('Province not found.');
                }

                $municipality = Municipality::where('name', 'Not Applicable')
                    ->where('province_id', $province->id)
                    ->first();
                if (!$municipality) {
                    throw new Exception('Municipality not found.');
                }

                $district = District::where('name', 'Not Applicable')
                    ->where('municipality_id', $municipality->id)
                    ->first();
                if (!$district) {
                    throw new Exception('District not found.');
                }

                $particularData = [
                    'sub_particular_id' => $data['sub_particular_id'],
                    'partylist_id' => $subParticular->name === 'Partylist' ||  $subParticular->fundSource->name === 'Partylist' ? $data['partylist_district'] : $notApplicablePartylist->id,
                    'district_id' => $subParticular->name === 'Partylist' ? $district->id : $data['partylist_district'],
                ];

                return Particular::create($particularData);

            } catch (Exception $e) {
                Notification::make()
                    ->title('Error')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'general' => $e->getMessage(),
                ]);
            }
        });
    }
}
