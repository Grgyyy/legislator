<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Filament\Resources\SubParticularResource;
use App\Helpers\Helper;
use App\Models\SubParticular;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditSubParticular extends EditRecord
{
    protected static string $resource = SubParticularResource::class;

    protected static ?string $title = 'Edit Particular Type';
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/particular-types' => 'Particular Types',
            'Edit'
        ];
    }

    protected function handleRecordUpdate($record, array $data): SubParticular
    {
        $this->validateUniqueSubParticular($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);
            
            NotificationHandler::sendSuccessNotification('Saved', 'Particular type has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the particular type: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the particular type update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueSubParticular($data, $currentId)
    {
        $subParticular = SubParticular::withTrashed()
            ->where('name', $data['name'])
            ->where('fund_source_id', $data['fund_source_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($subParticular) {
            $message = $subParticular->deleted_at 
                ? 'A particular type for the selected fund source has been deleted and must be restored before reuse.' 
                : 'A particular type for the selected fund source already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}