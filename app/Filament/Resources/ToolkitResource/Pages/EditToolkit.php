<?php

namespace App\Filament\Resources\ToolkitResource\Pages;

use App\Filament\Resources\ToolkitResource;
use App\Models\Toolkit;
use App\Services\NotificationHandler;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditToolkit extends EditRecord
{
    protected static string $resource = ToolkitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction()
                ->label('Exit'),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function isEdit(): bool
    {
        return true; // Edit mode
    }
    
    protected function handleRecordUpdate($record, array $data): Toolkit
    {
        return DB::transaction(function () use ($record, $data) {

            $existingRecord = Toolkit::where('lot_name', $data['lot_name'])
                ->where('year', $data['year'])
                ->whereNot('id', $record['id'])
                ->first();

            if ($existingRecord) {
                NotificationHandler::sendErrorNotification('Record Exists', "A record for this Qualification Title toolkit for '{$data['year']}' already exists.");
                return $existingRecord; 
            }

            

            if ($data['number_of_toolkit']) {

                $difference = $data['number_of_toolkit'] - $record['number_of_toolkit'];

                $new_anot = $record['available_number_of_toolkit'] + $difference;

                $record->update([
                    'lot_name' => $data['lot_name'],
                    'price_per_toolkit' => $data['price_per_toolkit'],
                    'available_number_of_toolkit' => $new_anot,
                    'number_of_toolkit' => $data['number_of_toolkit'],
                    'total_abc_per_lot' => $data['price_per_toolkit'] * $data['number_of_toolkit'],
                    'number_of_items_per_toolkit' => $data['number_of_items_per_toolkit'],
                    'year' => $data['year']
                ]);
            }
            else {
                $record->update([
                    'lot_name' => $data['lot_name'],
                    'price_per_toolkit' => $data['price_per_toolkit'],
                    'number_of_items_per_toolkit' => $data['number_of_items_per_toolkit'],
                    'year' => $data['year']
                ]);
            }
            

            NotificationHandler::sendSuccessNotification('Updated', 'The toolkit has been successfully updated.');

            return $record;
        });
    }

}
