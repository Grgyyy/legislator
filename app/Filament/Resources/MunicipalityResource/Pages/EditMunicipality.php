<?php
namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\District;
use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\DB;

class EditMunicipality extends EditRecord
{
    protected static string $resource = MunicipalityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['district_id'] = $this->record->district()->pluck('districts.id')->toArray() ?? [];
        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Municipality
    {
        DB::transaction(function () use ($record, $data) {
            $record->update([
                'name' => $data['name'],
                'class' => $data['class'],
                'code' => $data['code'],
                'province_id' => $data['province_id'],
            ]);

            $record->district()->sync($data['district_id'] ?? []);
        });

        // NotificationHandler::sendSuccessNotification('Saved', 'Municipality has been updated successfully.');

        return $record;
    }

    protected function afterSave(): void
    {
        $this->record->district()->sync($this->data['district_id'] ?? []);
    }
}
