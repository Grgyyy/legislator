<?php
namespace App\Filament\Clusters\Sectors\Resources\PriorityResource\Pages;

use App\Models\Priority;
use App\Filament\Clusters\Sectors\Resources\PriorityResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditPriority extends EditRecord
{
    protected static string $resource = PriorityResource::class;

    protected static ?string $title = 'Edit Priority Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/priorities' => 'Top Ten Priority Sectors',
            'Edit'
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Priority
    {
        $this->validateUniquePriority($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'Priority sector has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the priority sector: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the priority sector update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniquePriority($data, $currentId)
    {
        $priority = Priority::withTrashed()
            ->where('name', $data['name'])
            ->whereNot('id', $currentId)
            ->first();

        if ($priority) {
            $message = $priority->deleted_at 
                ? 'This priority sector has been deleted and must be restored before reuse.'
                : 'A priority sector with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}