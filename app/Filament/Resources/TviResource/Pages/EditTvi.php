<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Models\Tvi;
use App\Filament\Resources\TviResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditTvi extends EditRecord
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Edit Institution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institutions',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): Tvi
    {
        // Validate for unique institution before updating
        $this->validateUniqueInstitution($data, $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Institution updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the institution: ' . $e->getMessage())
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

    protected function validateUniqueInstitution(array $data, $currentId)
    {
        $query = Tvi::withTrashed()
            ->where('name', $data['name'])
            ->where('institution_class_id', $data['institution_class_id'])
            ->where('tvi_class_id', $data['tvi_class_id'])
            ->where('district_id', $data['district_id'])
            ->where('address', $data['address'])
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'An institution with these details exists but is marked as deleted. It cannot be updated.';
            } else {
                $message = 'An institution with these details already exists.';
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
