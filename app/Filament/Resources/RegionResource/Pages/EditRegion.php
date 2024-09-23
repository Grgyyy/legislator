<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Models\Region;
use App\Filament\Resources\RegionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditRegion extends EditRecord
{
    protected static string $resource = RegionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Region
    {
        $this->validateUniqueRegion($data['name'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Region record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the region: ' . $e->getMessage())
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

    protected function validateUniqueRegion($name, $currentId)
    {
        $query = Region::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Region data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Region data already exists.';
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
