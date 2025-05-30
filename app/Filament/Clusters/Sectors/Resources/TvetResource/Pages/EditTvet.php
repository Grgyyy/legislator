<?php
namespace App\Filament\Clusters\Sectors\Resources\TvetResource\Pages;

use App\Filament\Clusters\Sectors\Resources\TvetResource;
use App\Helpers\Helper;
use App\Models\Tvet;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;

class EditTvet extends EditRecord
{
    protected static string $resource = TvetResource::class;

    protected static ?string $title = 'Edit TVET Sectors';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/tvets' => 'TVET Sectors',
            'Edit'
        ];
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

    protected function handleRecordUpdate($record, array $data): Tvet
    {
        $this->validateUniqueTvet($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

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

    protected function validateUniqueTvet($data, $currentId)
    {
        $tvet = Tvet::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->whereNot('id', $currentId)
            ->first();

        if ($tvet) {
            $message = $tvet->deleted_at
                ? 'A TVET sector with this name has been deleted and must be restored before reuse.'
                : 'A TVET sector with this name already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}