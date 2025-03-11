<?php
namespace App\Filament\Resources\InstitutionRecognitionResource\Pages;

use App\Filament\Resources\InstitutionRecognitionResource;
use App\Imports\InstitutionRecognitionImport;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

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

    public function getTitle(): string
    {
        $tviId = $this->getTviId();
        $tvi = Tvi::find($tviId);

        return $tvi ? $tvi->name : 'Institution';
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

            Action::make('InstitutionRecognitionImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('file')
                        ->required()
                        ->markAsRequired(false)
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                ])
                ->action(function (array $data) {
                    if (isset($data['file']) && is_string($data['file'])) {
                        $filePath = storage_path('app/' . $data['file']);

                        try {
                            Excel::import(new InstitutionRecognitionImport, $filePath);
                            NotificationHandler::sendSuccessNotification('Import Successful', 'The Institution Recognitions have been successfully imported from the file.');
                        } catch (Exception $e) {
                            NotificationHandler::sendErrorNotification('Import Failed', 'There was an issue importing the institution recognitions: ' . $e->getMessage());
                        } finally {
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }),
        ];
    }

    protected function getTviId(): ?int
    {
        return (int) request()->route('record');
    }
}
