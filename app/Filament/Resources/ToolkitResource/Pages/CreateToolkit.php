<?php

namespace App\Filament\Resources\ToolkitResource\Pages;

use App\Filament\Resources\ToolkitResource;
use App\Models\Toolkit;
use App\Services\NotificationHandler;
use DB;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateToolkit extends CreateRecord
{
    protected static string $resource = ToolkitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function isEdit(): bool
    {
        return false; // Edit mode
    }

    protected function handleRecordCreation(array $data): Toolkit
    {
        // Validate input data
        return DB::transaction(function () use ($data) {
            $existingRecord = Toolkit::where('qualification_title_id', $data['qualification_title_id'])
                ->where('year', $data['year'])
                ->first();

            if ($existingRecord) {
                NotificationHandler::sendErrorNotification('Record Exists', "A record for this Qualification Title toolkit for '{$data['year']}' already exists.");
                return $existingRecord; // Returning existing record prevents duplicate entries
            }

            $toolkit = Toolkit::create([
                'qualification_title_id' => $data['qualification_title_id'],
                'price_per_toolkit' => $data['price_per_toolkit'],
                'available_number_of_toolkit' => $data['number_of_toolkit'],
                'number_of_toolkit' => $data['number_of_toolkit'],
                'total_abc_per_lot' => $data['price_per_toolkit'] * $data['number_of_toolkit'],
                'number_of_items_per_toolkit' => $data['number_of_items_per_toolkit'],
                'year' => $data['year'],
            ]);

            NotificationHandler::sendSuccessNotification('Created', 'The toolkit has been successfully linked to the qualification.');

            return $toolkit;
        });
    }
}
