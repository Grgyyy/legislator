<?php
namespace App\Filament\Resources\PriorityResource\Pages;

use App\Models\Priority;
use App\Filament\Resources\PriorityResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class EditPriority extends EditRecord
{
    protected static string $resource = PriorityResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/priorities' => 'Top Ten Priority Sectors',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Priority
    {
        $this->validateUniquePriority($data['name'], $record->id);

        try {
            $record->update($data);

            Notification::make()
                ->title('Priority record updated successfully')
                ->success()
                ->send();

            return $record;
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the priority: ' . $e->getMessage())
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

    protected function validateUniquePriority($name, $currentId)
    {
        $query = Priority::withTrashed()
            ->where('name', $name)
            ->where('id', '!=', $currentId)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Priority sector data exists and is marked as deleted. Data cannot be updated.';
            } else {
                $message = 'Priority sector data already exists.';
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
