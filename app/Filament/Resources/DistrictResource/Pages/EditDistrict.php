<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Models\District;
use App\Filament\Resources\DistrictResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getRedirectUrl(): string
    {
        $municipalityId = $this->record->municipality_id;

        if ($municipalityId) {
            return route('filament.admin.resources.municipalities.showDistricts', ['record' => $municipalityId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): District
    {
        // Validate for unique district name within the same municipality
        $this->validateUniqueDistrict($data['name'], $data['municipality_id'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('District record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the district: ' . $e->getMessage())
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

    protected function validateUniqueDistrict($name, $municipalityId, $currentId)
    {
        $query = District::withTrashed()
            ->where('name', $name)
            ->where('municipality_id', $municipalityId)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'District data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'District data already exists in this municipality.';
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
