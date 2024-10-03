<?php

namespace App\Filament\Resources\SubParticularResource\Pages;

use App\Models\SubParticular;
use App\Filament\Resources\SubParticularResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSubParticular extends CreateRecord
{
    protected static string $resource = SubParticularResource::class;

    protected static ?string $title = 'Create Particular Type';

    public function getBreadcrumbs(): array
    {
        return [
            '/sub-particulars' => 'Particular Types',
            'Create'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): SubParticular
    {
        $this->validateUniqueSubParticular($data['name'], $data['fund_source_id']);

        $subParticular = DB::transaction(fn() => SubParticular::create([
            'name' => $data['name'],
            'fund_source_id' => $data['fund_source_id']
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Particular type has been created successfully.');

        return $subParticular;
    }

    protected function validateUniqueSubParticular($name, $fundSourceId)
    {
        $subParticular = SubParticular::withTrashed()
            ->where('name', $name)
            ->where('fund_source_id', $fundSourceId)
            ->first();

        if ($subParticular) {
            $message = $subParticular->deleted_at 
                ? 'This particular type has been deleted and must be restored before reuse.' 
                : 'A particular type with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}