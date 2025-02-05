<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Helpers\Helper;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Exception;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class EditProjectProposal extends EditRecord
{
    protected static string $resource = ProjectProposalResource::class;
    
    protected static ?string $title = 'Edit Project Proposal Program';

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
    
    public function getBreadcrumbs(): array
    {
        return [
            '/project-proposals' => 'Project Proposal Programs',
            'Create',
        ];
    }

    public function disabledSoc(): bool
    {
        return true;
    }

    public function noQualiCode(): bool
    {
        return true;
    }

    public function noSocCode(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $data['program_name'] = $record->title;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->validateUniqueProjectProposal($data, $record->id);

        $data['title'] = Helper::capitalizeWords($data['title']);

        try {
            $record->update($data);


            NotificationHandler::sendSuccessNotification('Saved', 'Project proposal program has been updated successfully.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to update the project proposal program: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the project proposal program update: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    private function validateUniqueProjectProposal($data, $currentId)
    {
        $trainingProgram = TrainingProgram::withTrashed()
            ->where('title', $data['title'])
            ->whereNot('id', $currentId)
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at
                ? 'A qualification title with the provided details has been deleted and must be restored before reuse.'
                : 'A qualification title with the provided details already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}