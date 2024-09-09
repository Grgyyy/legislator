<?php

namespace App\Filament\Resources\AbddResource\Pages;

use App\Models\Abdd;
use App\Filament\Resources\AbddResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditAbdd extends EditRecord
{
    protected static string $resource = AbddResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/abdds' => 'ABDD Sectors',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Abdd
    {
        $this->validateUniqueAbdd($data['name'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('ABDD record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the ABDD record: ' . $e->getMessage())
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

    protected function validateUniqueAbdd($name, $currentId)
    {
        $query = Abdd::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'ABDD Sector data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'ABDD Sector data already exists.';
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
