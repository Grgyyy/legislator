<?php

namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditMunicipality extends EditRecord
{
    protected static string $resource = MunicipalityResource::class;

    protected function getRedirectUrl(): string
    {
        $provinceId = $this->record->province_id;

        if ($provinceId) {
            return route('filament.admin.resources.provinces.showMunicipalities', ['record' => $provinceId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Municipality
    {
        // Validate for unique municipality name within the same province
        $this->validateUniqueMunicipality($data['name'], $data['province_id'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Municipality record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the municipality: ' . $e->getMessage())
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

    protected function validateUniqueMunicipality($name, $provinceId, $currentId)
    {
        $query = Municipality::withTrashed()
            ->where('name', $name)
            ->where('province_id', $provinceId)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Municipality data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Municipality data already exists in this province.';
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
