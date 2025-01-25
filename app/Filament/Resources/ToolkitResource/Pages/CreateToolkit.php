<?php

namespace App\Filament\Resources\ToolkitResource\Pages;

use App\Filament\Resources\ToolkitResource;
use App\Models\QualificationTitle;
use App\Models\Toolkit;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateToolkit extends CreateRecord
{
    protected static string $resource = ToolkitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
        return false; // Create mode
    }

    protected function handleRecordCreation(array $data): Toolkit
    {
        return DB::transaction(function () use ($data) {
            
            $existingRecord = Toolkit::where('lot_name', $data['lot_name'])
                ->where('year', $data['year'])
                ->first();

            if ($existingRecord) {
                $message = "{$data['lot_name']} toolkit for {$data['year']} is already existing.";

                NotificationHandler::handleValidationException('Something went wrong', $message);
            }

            

            if ($data['number_of_toolkit']) {
                $toolkit = Toolkit::create([
                    'lot_name' => $data['lot_name'],
                    'price_per_toolkit' => $data['price_per_toolkit'],
                    'available_number_of_toolkit' => $data['number_of_toolkit'],
                    'number_of_toolkit' => $data['number_of_toolkit'],
                    'total_abc_per_lot' => $data['price_per_toolkit'] * $data['number_of_toolkit'],
                    'number_of_items_per_toolkit' => $data['number_of_items_per_toolkit'],
                    'year' => $data['year']
                ]);
            }
            else {
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

            NotificationHandler::sendSuccessNotification(
                'Created',
                'The toolkit has been successfully created.'
            );

            return $toolkit;
        });
    }
}
