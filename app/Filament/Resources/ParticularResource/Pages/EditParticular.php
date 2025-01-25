<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Models\Particular;
use App\Models\SubParticular;
use App\Models\Partylist;
use App\Models\District;
use App\Models\Province;
use App\Models\Region;
use App\Filament\Resources\ParticularResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class EditParticular extends EditRecord
{
    protected static string $resource = ParticularResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $notApplicablePartylist = Partylist::where('name', 'Not Applicable')->first();

        if ($record->partylist_id === $notApplicablePartylist?->id) {
            $data['administrative_area'] = $record->district_id;
        } else {
            $data['administrative_area'] = $record->partylist_id;
        }

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Particular
    {
        return DB::transaction(function () use ($record, $data) {
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

                $this->validateUniqueParticular($data['sub_particular_id'], $partylistId, $districtId, $record->id);

                $particularData = [
                    'sub_particular_id' => $data['sub_particular_id'],
                    'partylist_id' => $partylistId,
                    'district_id' => $districtId,
                ];

                $record->update($particularData);

                NotificationHandler::sendSuccessNotification('Success', 'Particular has been updated successfully.');

                return $record;
            } catch (Exception $e) {
                NotificationHandler::sendErrorNotification('Error', $e->getMessage());

                throw ValidationException::withMessages([
                    'general' => $e->getMessage(),
                ]);
            }
        });
    }

    protected function validateUniqueParticular($sub_particular_id, $partylist_id, $district_id, $currentId = null)
    {
        $existingParticular = Particular::withTrashed()
            ->where('sub_particular_id', $sub_particular_id)
            ->where('partylist_id', $partylist_id)
            ->where('district_id', $district_id)
            ->whereNot('id', $currentId)
            ->first();

        if ($existingParticular) {
            $message = $existingParticular->deleted_at 
                ? 'This particular has been deleted and must be restored before reuse.' 
                : 'A particular with the specified type, party-list, and district already exists.';

            NotificationHandler::handleValidationException('Validation Error', $message);
        }
    }
}