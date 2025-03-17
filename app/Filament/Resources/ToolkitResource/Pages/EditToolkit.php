<?php

namespace App\Filament\Resources\ToolkitResource\Pages;

use App\Filament\Resources\ToolkitResource;
use App\Helpers\Helper;
use App\Models\Toolkit;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditToolkit extends EditRecord
{
    protected static string $resource = ToolkitResource::class;

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
    public function isEdit(): bool
    {
        return true;
    }

    protected function handleRecordUpdate($record, array $data): Toolkit
    {

        $this->validateUniqueToolkit($data, $record->id);

        $data['lot_name'] = Helper::capitalizeWords($data['lot_name']);

        return DB::transaction(function () use ($record, $data) {
            $difference = $data['number_of_toolkit'] - $record->number_of_toolkits;
            $new_available_toolkits = max(0, $record->available_number_of_toolkits + $difference);

            $record->update([
                'lot_name' => $data['lot_name'],
                'price_per_toolkit' => $data['price_per_toolkit'],
                'available_number_of_toolkits' => min($data['number_of_toolkit'], $new_available_toolkits),
                'number_of_toolkits' => $data['number_of_toolkit'],
                'total_abc_per_lot' => $data['price_per_toolkit'] * $data['number_of_toolkit'],
                'number_of_items_per_toolkit' => $data['number_of_items_per_toolkit'],
                'year' => $data['year'],
            ]);

            if (!empty($data['qualification_title_id'])) {
                $record->qualificationTitles()->sync($data['qualification_title_id']);
            }

            NotificationHandler::sendSuccessNotification('Saved', 'Toolkit has been updated successfully.');

            return $record;
        });
    }

    protected function afterSave(): void
    {
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->record)
            ->event('Updated')
            ->withProperties([
                'lot_name' => $this->record->lot_name,
                'price_per_toolkit' => $this->record->price_per_toolkit ?? null,
                'qualification_title' => $this->record->qualificationTitles->implode('trainingProgram.title', ', '),
                'available_number_of_toolkits' => $this->record->available_number_of_toolkits,
                'number_of_toolkits' => $this->record->number_of_toolkits,
                'total_abc_per_lot' => $this->record->total_abc_per_lot,
                'number_of_items_per_toolkit' => $this->record->number_of_items_per_toolkit,
                'year' => $this->record->year,
            ])
            ->log("An Tookit for '{$this->record->lot_name}' has been created.");
    }



    protected function validateUniqueToolkit($data, $currentId)
    {
        $toolkit = Toolkit::withTrashed()
            ->where('lot_name', $data['lot_name'])
            ->where('year', $data['year'])
            ->whereNot('id', $currentId)
            ->first();

        if ($toolkit) {
            $message = $toolkit->deleted_at
                ? "{$data['lot_name']} toolkits for {$data['year']} has been deleted and must be restored before reuse."
                : "{$data['lot_name']} toolkits for {$data['year']} already exists.";

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}
