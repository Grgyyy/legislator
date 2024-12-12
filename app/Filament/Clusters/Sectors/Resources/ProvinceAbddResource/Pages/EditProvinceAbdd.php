<?php

namespace App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource\Pages;

use App\Filament\Clusters\Sectors\Resources\ProvinceAbddResource;
use App\Models\ProvinceAbdd;
use App\Services\NotificationHandler;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditProvinceAbdd extends EditRecord
{
    protected static string $resource = ProvinceAbddResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function isEdit(): bool
    {
        return true; // Edit mode
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle record update logic with validation.
     *
     * @param \Illuminate\Database\Eloquent\Model $record
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $this->validateUpdateData($data);

        return DB::transaction(function () use ($record, $data) {
            $existingRecord = ProvinceAbdd::where('province_id', $data['province_id'])
                ->where('abdd_id', $data['abdd_id'])
                ->where('year', $data['year'])
                ->where('id', '!=', $record->id) 
                ->first();

            if ($existingRecord) {
                NotificationHandler::sendErrorNotification('Record Exists', 'A record for this Province, ABDD, and Year already exists.');
                return $record;
            }

            $record->update([
                'province_id' => $data['province_id'],
                'abdd_id' => $data['abdd_id'],
                'available_slots' => $data['total_slots'],
                'total_slots' => $data['total_slots'],
                'year' => $data['year'],
            ]);

            NotificationHandler::sendSuccessNotification('Updated', 'Province ABDD has been updated successfully.');

            return $record;
        });
    }

    /**
     * Validate the update data
     *
     * @param array $data
     * @throws ValidationException
     */
    protected function validateUpdateData(array $data): void
    {
        $validator = \Validator::make($data, [
            'province_id' => ['required', 'integer'],
            'abdd_id' => ['required', 'integer'],
            'year' => ['required', 'numeric'],
            'total_slots' => ['required', 'numeric'],
            'available_slots' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            NotificationHandler::sendErrorNotification('Validation Error', 'There was an issue with the provided data.');
            throw new ValidationException($validator);
        }
    }
}
