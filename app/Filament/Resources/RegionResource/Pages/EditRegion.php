<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Models\Region;
use App\Filament\Resources\RegionResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;

class EditRegion extends EditRecord
{
    protected static string $resource = RegionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function handleRecordUpdate($record, array $data): Region
    {
        $this->validateUniqueRegion($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);

        try {
            $record->update($data);
            
            NotificationHandler::sendSuccessNotification('Saved', 'Region has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the region: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the region update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueRegion($data, $currentId)
    {
        $region = Region::withTrashed()
            ->where('name', $data['name'])
            ->whereNot('id', $currentId)
            ->first();

        if ($region) {
            $message = $region->deleted_at 
                ? 'This region has been deleted and must be restored before reuse.' 
                : 'A region with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = Region::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->whereNot('id', $currentId)
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A region with this PSG code already exists and has been deleted.' 
                    : 'A region with this PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}