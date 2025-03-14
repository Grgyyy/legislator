<?php
namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use App\Filament\Resources\InstitutionRecognitionResource;
use App\Models\Tvi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ShowInstitutionRecognition extends ListRecords
{
    protected static string $resource = InstitutionRecognitionResource::class;


    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        $tviId = $this->getTviId();

        $tvi = Tvi::find($tviId);

        return [
            route('filament.admin.resources.institution-recognitions.showRecognition', ['record' => $tvi->id]) => $tvi ? $tvi->name : 'Institution',
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

    protected function getTableQuery(): Builder|null
    {
        $tviId = $this->getTviId();

        return parent::getTableQuery()->where('tvi_id', $tviId);
    }

    protected function getTviId(): ?int
    {
        $tviId = request()->route('record') ?? session('tvi_id');

        if ($tviId) {
            session(['tvi_id' => $tviId]);
        }

        return (int) $tviId;
    }
}
