<?php

namespace App\Filament\Resources\TviTypeResource\Pages;

use App\Models\TviType;
use App\Filament\Resources\TviTypeResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditTviType extends EditRecord
{
    protected static string $resource = TviTypeResource::class;

    protected static ?string $title = 'Edit Institution Type';

    public function getBreadcrumbs(): array
    {
        return [
            '/tvi-types' => 'Institution Types',
            'Edit',
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): TviType
    {
        $this->validateUniqueTviType($data['name'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Institution type has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the institution type: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the institution type update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueTviType($name, $currentId)
    {
        $tviType = TviType::withTrashed()
            ->where('name', $name)
            ->whereNot('id', $currentId)
            ->first();

        if ($tviType) {
            $message = $tviType->deleted_at 
                ? 'This institution type has been deleted. Restoration is required before it can be reused.' 
                : 'An institution type with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}