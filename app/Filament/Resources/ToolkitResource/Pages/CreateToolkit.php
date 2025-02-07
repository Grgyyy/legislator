<?php

namespace App\Filament\Resources\ToolkitResource\Pages;

use App\Filament\Resources\ToolkitResource;
use App\Helpers\Helper;
use App\Models\Toolkit;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateToolkit extends CreateRecord
{
    protected static string $resource = ToolkitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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

    public function isEdit(): bool
    {
        return false;
    }

    protected function handleRecordCreation(array $data): Toolkit
    {
        $this->validateUniqueToolkit($data);
        
        $data['lot_name'] = Helper::capitalizeWords($data['lot_name']);

        return DB::transaction(function () use ($data) {          
            if ($data['number_of_toolkit']) {
                $toolkit = Toolkit::create([
                    'lot_name' => $data['lot_name'],
                    'price_per_toolkit' => $data['price_per_toolkit'],
                    'available_number_of_toolkits' => $data['number_of_toolkit'],
                    'number_of_toolkits' => $data['number_of_toolkit'],
                    'total_abc_per_lot' => $data['price_per_toolkit'] * $data['number_of_toolkit'],
                    'number_of_items_per_toolkit' => $data['number_of_items_per_toolkit'],
                    'year' => $data['year']
                ]);
            } else {
                $toolkit = Toolkit::create([
                    'lot_name' => $data['lot_name'],
                    'price_per_toolkit' => $data['price_per_toolkit'],
                    'number_of_items_per_toolkit' => $data['number_of_items_per_toolkit'],
                    'year' => $data['year']
                ]);
            }

            if ($toolkit) {
                $toolkit->qualificationTitles()->sync($data['qualification_title_id']);
            }

            NotificationHandler::sendSuccessNotification('Created', 'The toolkit has been successfully created.');

            return $toolkit;
        });
    }

    protected function validateUniqueToolkit($data)
    {
        $toolkit = Toolkit::withTrashed()
            ->where('lot_name', $data['lot_name'])
            ->where('year', $data['year'])
            ->first();

        if ($toolkit) {
            $message = $toolkit->deleted_at 
                ? "{$data['lot_name']} toolkits for {$data['year']} has been deleted and must be restored before reuse."
                : "{$data['lot_name']} toolkits for {$data['year']} already exists.";
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}