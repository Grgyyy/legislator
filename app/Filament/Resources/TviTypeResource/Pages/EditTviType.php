<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Models\TviType;
use App\Filament\Resources\TviTypeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditTviType extends EditRecord
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Edit Institution Type';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            'Institution Types',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): TviType
    {
        // Validate for unique TVI type name
        $this->validateUniqueTviType($data['name'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Institution Type record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the institution type: ' . $e->getMessage())
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

    protected function validateUniqueTviType($name, $currentId)
    {
        $query = TviType::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Institution Type data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Institution Type data already exists.';
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
