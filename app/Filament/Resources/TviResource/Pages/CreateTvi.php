<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Filament\Resources\TviResource;
use App\Helpers\Helper;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateTvi extends CreateRecord
{
    protected static string $resource = TviResource::class;

    protected static ?string $title = 'Create Institution';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            '/institutions' => 'Institutions',
            'Create',
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save & Exit'),
            $this->getCreateAnotherFormAction()
                ->label('Save & Create Another'),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }

    protected function handleRecordCreation(array $data): Tvi
    {
        $this->validateUniqueInstitution($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $tvi = DB::transaction(fn() => Tvi::create([
            'name' => $data['name'],
            'school_id' => $data['school_id'],
            'district_id' => $data['district_id'],
            'municipality_id' => $data['municipality_id'],
            'tvi_type_id' => $data['tvi_type_id'],
            'tvi_class_id' => $data['tvi_class_id'],
            'institution_class_id' => $data['institution_class_id'],
            // 'status_id' => $data['status_id'],
            'address' => $data['address'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Institution has been created successfully.');

        return $tvi;
    }

    protected function validateUniqueInstitution($data)
    {
        $tvi = Tvi::withTrashed()
            ->whereRaw('TRIM(name) = ?', trim($data['name']))
            ->where('school_id', $data['school_id'])
            ->first();

        if ($tvi) {
            $message = $tvi->deleted_at
                ? 'This institution with the provided details has been deleted and must be restored before reuse.'
                : 'An institution with the provided details already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!empty($data['code'])) {
            $schoolId = Tvi::withTrashed()
                ->where('school_id', $data['school_id'])
                ->first();

            if ($schoolId) {
                $message = $schoolId->deleted_at
                    ? 'An institution with this school ID already exists and has been deleted.'
                    : 'An institution with this school ID already exists.';

                NotificationHandler::handleValidationException('Invalid School ID', $message);
            }
        }
    }
}
