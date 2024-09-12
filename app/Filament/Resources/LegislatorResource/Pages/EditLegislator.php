<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Models\Legislator;
use App\Filament\Resources\LegislatorResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditLegislator extends EditRecord
{
    protected static string $resource = LegislatorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Legislator
    {
        // Validate for unique legislator name
        $this->validateUniqueLegislator($data['name'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Legislator record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the legislator: ' . $e->getMessage())
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

    protected function validateUniqueLegislator($name, $currentId)
    {
        $query = Legislator::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Legislator data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Legislator data already exists.';
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
