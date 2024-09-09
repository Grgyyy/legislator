<?php

namespace App\Filament\Resources\InstitutionClassResource\Pages;

use App\Models\InstitutionClass;
use App\Filament\Resources\InstitutionClassResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditInstitutionClass extends EditRecord
{
    protected static string $resource = InstitutionClassResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): InstitutionClass
    {
        // Validate for unique institution class name
        $this->validateUniqueInstitutionClass($data['name'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Institution Class record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the institution class: ' . $e->getMessage())
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

    protected function validateUniqueInstitutionClass($name, $currentId)
    {
        $query = InstitutionClass::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Institution Class data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Institution Class data already exists.';
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
