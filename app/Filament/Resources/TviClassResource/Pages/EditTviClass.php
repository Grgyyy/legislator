<?php

namespace App\Filament\Resources\TviClassResource\Pages;

use App\Models\TviClass;
use App\Filament\Resources\TviClassResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditTviClass extends EditRecord
{
    protected static string $resource = TviClassResource::class;

    protected static ?string $title = 'Edit Institution Class';
    
    public function getBreadcrumbs(): array
    {
        return [
            '/tvi-classes' => 'Institution Classes',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): TviClass
    {
        $this->validateUniqueTviClass($data['name'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Institution class has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the institution class: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the institution class update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueTviClass($name, $currentId)
    {
        $tviClass = TviClass::withTrashed()
            ->where('name', $name)
            ->whereNot('id', $currentId)
            ->first();

        if ($tviClass) {
            $message = $tviClass->deleted_at 
                ? 'This institution class has been deleted. Restoration is required before it can be reused.' 
                : 'An institution class with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}