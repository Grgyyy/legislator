<?php
namespace App\Filament\Resources\MunicipalityResource\Pages;

use App\Models\Municipality;
use App\Filament\Resources\MunicipalityResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditMunicipality extends EditRecord
{
    protected static string $resource = MunicipalityResource::class;

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['district_id'] = $this->record->district()->pluck('districts.id')->toArray() ?? [];
        
        return $data;
    }

    protected function handleRecordUpdate($record, array $data): Municipality
    {
        $this->validateUniqueMunicipality($data, $record->id);

        $data['name'] = Helper::capitalizeWords($data['name']);
        $data['class'] = Helper::capitalizeWords($data['class']);

        DB::transaction(function () use ($record, $data) {
            $record->update([
                'name' => $data['name'],
                'class' => $data['class'],
                'code' => $data['code'],
                'province_id' => $data['province_id'],
            ]);

            $record->district()->sync($data['district_id'] ?? []);
        });

        NotificationHandler::sendSuccessNotification('Saved', 'Municipality has been updated successfully.');

        return $record;
    }

    protected function afterSave(): void
    {
        $this->record->district()->sync($this->data['district_id'] ?? []);
    }

    protected function validateUniqueMunicipality($data, $currentId)
    {
        $municipality = Municipality::withTrashed()
            ->where('name', $data['name'])
            ->where('province_id', $data['province_id'])
            ->whereNot('id', $currentId)
            ->first();

        if ($municipality) {
            $message = $municipality->deleted_at
                ? 'A municipality with this name already exists in the province but has been deleted; it must be restored before reuse.'
                : 'A municipality with this name already exists in the specified province.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $code = Municipality::withTrashed()
                ->whereRaw('CAST(code AS UNSIGNED) = ?', [(int)$data['code']])
                ->whereNot('id', $currentId)
                ->first();

            if ($code) {
                $message = $code->deleted_at 
                    ? 'A municipality with the provided PSG code already exists and has been deleted.' 
                    : 'A municipality with the provided PSG code already exists.';
            
                NotificationHandler::handleValidationException('Invalid Code', $message);
            }
        }
    }
}