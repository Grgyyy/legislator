<?php

namespace App\Filament\Resources\ProvinceResource\Pages;

use App\Models\Province;
use App\Filament\Resources\ProvinceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditProvince extends EditRecord
{
    protected static string $resource = ProvinceResource::class;

    protected function getRedirectUrl(): string
    {
        $regionId = $this->record->region_id;

        if ($regionId) {
            return route('filament.admin.resources.regions.show_provinces', ['record' => $regionId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Province
    {
        // Validate for unique province name within the same region
        $this->validateUniqueProvince($data['name'], $data['region_id'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Province record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the province: ' . $e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An unexpected error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        return $record;
    }

    protected function validateUniqueProvince($name, $regionId, $currentId)
    {
        $query = Province::withTrashed()
            ->where('name', $name)
            ->where('region_id', $regionId)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Province data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Province data already exists in this region.';
            }
            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }
}
