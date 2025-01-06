<?php

namespace App\Filament\Resources\TviResource\Pages;

use App\Models\Tvi;
use App\Filament\Resources\TviResource;
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
            '/tvis' => 'Institutions',
            'Create',
        ];
    }

    protected function handleRecordCreation(array $data): Tvi
    {
        $data['name'] = strtolower($data['name']);
        $data['address'] = strtolower($data['address']);

        $this->validateUniqueInstitution($data);

        $tvi = DB::transaction(fn() => Tvi::create($data));

        NotificationHandler::sendSuccessNotification('Created', 'Institution has been created successfully.');

        return $tvi;
    }

    protected function validateUniqueInstitution($data)
    {
        $tvi = Tvi::withTrashed()
            ->where('name', $data['name'])
            ->where('institution_class_id', $data['institution_class_id'])
            ->where('tvi_class_id', $data['tvi_class_id'])
            ->where('district_id', $data['district_id'])
            ->where('address', $data['address'])
            ->first();

        if ($tvi) {
            $message = $tvi->deleted_at
                ? 'This institution with the provided details has been deleted and must be restored before reuse.'
                : 'An institution with the provided details already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

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
