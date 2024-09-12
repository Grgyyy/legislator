<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Models\Particular;
use App\Filament\Resources\ParticularResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditParticular extends EditRecord
{
    protected static string $resource = ParticularResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Particular
    {
        // Validate for unique particular name within the same district
        $this->validateUniqueParticular($data['name'], $data['district_id'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Particular record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the particular: ' . $e->getMessage())
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

    protected function validateUniqueParticular($name, $districtId, $currentId)
    {
        $query = Particular::withTrashed()
            ->where('name', $name)
            ->where('district_id', $districtId)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Particular data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Particular data already exists in this district.';
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
