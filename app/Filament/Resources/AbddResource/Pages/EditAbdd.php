<?php

namespace App\Filament\Resources\AbddResource\Pages;

use App\Filament\Resources\AbddResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;

class EditAbdd extends EditRecord
{
    protected static string $resource = AbddResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            '/abdds' => 'ABDD Sectors',
            'Edit'
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function update(array $data): void
    {
        try {
            parent::update($data);

            Notification::make()
                ->title('ABDD record updated successfully')
                ->success()
                ->send();
        } catch (QueryException $e) {
            Notification::make()
                ->title('Database Error')
                ->body('An error occurred while updating the ABDD record: ' . $e->getMessage())
                ->danger()
                ->send();
        } catch (\Exception $e) {
            // Notify general error
            Notification::make()
                ->title('Error')
                ->body('An unexpected error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
