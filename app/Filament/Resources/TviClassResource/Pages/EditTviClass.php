<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Models\TviClass;
use App\Filament\Resources\TviClassResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class EditTviClass extends EditRecord
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Edit Institution Class';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Classes',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): TviClass
    {
        // Validate for unique TVI class name
        $this->validateUniqueTviClass($data['name'], $record->id);

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

    protected function validateUniqueTviClass($name, $currentId)
    {
        $query = TviClass::withTrashed()
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
