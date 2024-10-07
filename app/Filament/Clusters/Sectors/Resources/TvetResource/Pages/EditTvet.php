<?php
namespace App\Filament\Clusters\Sectors\Resources\TvetResource\Pages;

use App\Models\Tvet;
use App\Filament\Clusters\Sectors\Resources\TvetResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditTvet extends EditRecord
{
    protected static string $resource = TvetResource::class;

    protected static ?string $title = 'Edit TVET Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/tvets' => 'TVET Sectors',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate($record, array $data): Tvet
    {
        $this->validateUniqueTvet($data['name'], $record->id);

        try {
            $record->update($data);

            NotificationHandler::sendSuccessNotification('Saved', 'TVET sector has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the TVET sector: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the TVET sector update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueTvet($name, $currentId)
    {
        $tvet = Tvet::withTrashed()
            ->where('name', $name)
            ->whereNot('id', $currentId)
            ->first();

        if ($tvet) {
            $message = $tvet->deleted_at 
                ? 'This TVET sector has been deleted. Restoration is required before it can be reused.' 
                : 'A TVET sector with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}