<?php
namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use App\Filament\Resources\InstitutionRecognitionResource;
use App\Models\Region;
use App\Filament\Resources\ProvinceResource;
use App\Models\Tvi;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ShowInstitutionRecognition extends ListRecords
{
    protected static string $resource = InstitutionRecognitionResource::class;

    public function getBreadcrumbs(): array
    {
        $tviId = $this->getTviId();

        $tvi = Tvi::find($tviId);

        return [
            route('filament.admin.resources.institution-recognitions.index') => $tvi ? $tvi->name : 'Institution',
            'Recognitions',
            'List'
        ];
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        $tviId = $this->getTviId();

        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.institution-recognitions.create', ['tvi_id' => $tviId])),
        ];
    }

    protected function getTviId(): ?int
    {
        return (int) request()->route('record');
    }
}
