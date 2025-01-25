<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use App\Filament\Resources\UserResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;


class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function handleRecordUpdate($record, array $data): User
    {
        $this->validateUniqueUser($data, $record->id);

        try {
            $record->update($data);
            
            NotificationHandler::sendSuccessNotification('Saved', 'User has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the user: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the user update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueUser($data, $currentId)
    {
        $user = User::withTrashed()
            ->where('email', $data['email'])
            ->whereNot('id', $currentId)
            ->first();

        if ($user) {
            $message = $user->deleted_at 
                ? 'This email has been deleted. Restoration is required before it can be reused.' 
                : 'This email is already associated with an account.';
            
            NotificationHandler::handleValidationException('Email Already In Use', $message);
        }
    }
}
