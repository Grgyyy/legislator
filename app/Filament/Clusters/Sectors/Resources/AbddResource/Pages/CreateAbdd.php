<?php

namespace App\Filament\Clusters\Sectors\Resources\AbddResource\Pages;

use App\Models\Abdd;
use App\Filament\Clusters\Sectors\Resources\AbddResource;
use App\Helpers\Helper;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateAbdd extends CreateRecord
{
    protected static string $resource = AbddResource::class;

    protected static ?string $title = 'Create ABDD Sectors';

    public function getBreadcrumbs(): array
    {
        return [
            '/sectors/abdds' => 'ABDD Sectors',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Abdd
    {
        $this->validateUniqueAbdd($data);

        $data['name'] = Helper::capitalizeWords($data['name']);

        $abdd = DB::transaction(fn () => Abdd::create([
                'name' => $data['name'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'ABDD sector has been created successfully.');

        return $abdd;
    }

    protected function validateUniqueAbdd($data)
    {
        $abdd = Abdd::withTrashed()
            ->where('name', $data['name'])
            ->first();

        if ($abdd) {
            $message = $abdd->deleted_at 
                ? 'This ABDD sector has been deleted and must be restored before reuse.'
                : 'An ABDD sector with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}