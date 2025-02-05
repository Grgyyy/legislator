<?php

namespace App\Filament\Resources\ProjectProposalResource\Pages;

use App\Filament\Resources\ProjectProposalResource;
use App\Helpers\Helper;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Exception;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class ConvertProjectProposal extends EditRecord
{
    protected static string $resource = ProjectProposalResource::class;

    protected static ?string $title = "Convert Project Proposal Program";
    
    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.qualification-titles.index');
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
            'Convert'
        ];
    }

    public function disabledSoc(): bool
    {
        return false;
    }

    public function noQualiCode(): bool
    {
        return false;
    }

    public function noSocCode(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $data['program_name'] = $record->title;

        $data['scholarshipPrograms'] = $record->scholarshipPrograms()
            ->pluck('scholarship_programs.id')
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->validateUniqueQualificationTitle($data, $record->id);

        $data['title'] = Helper::capitalizeWords($data['title']);

         try {
            $record->update([
                'code' => $data['code'],
                'soc_code' => $data['soc_code'],
                'full_coc_ele' => $data['full_coc_ele'],
                'nc_level' => $data['nc_level']  ?? null,
                'title'       => $data['title'],
                'priority_id' => $data['priority_id'],
                'tvet_id'     => $data['tvet_id'],
                'soc' => 1
            ]);

            NotificationHandler::sendSuccessNotification('Saved', 'Project proposal program has been successfully converted into a qualification title and is now ready for costing in the schedule of cost.');

            return $record;
        } catch (QueryException $e) {
            NotificationHandler::sendErrorNotification('Database Error', 'A database error occurred while attempting to convert the project proposal program: ' . $e->getMessage() . ' Please review the details and try again.');
        } catch (Exception $e) {
            NotificationHandler::sendErrorNotification('Unexpected Error', 'An unexpected issue occurred during the conversion of project proposal program: ' . $e->getMessage() . ' Please try again or contact support if the problem persists.');
        }

        return $record;
    }

    protected function validateUniqueQualificationTitle($data, $currentId)
    {
        $trainingProgram = TrainingProgram::withTrashed()
            ->where('soc_code', $data['soc_code'])
            ->whereNot('id', $currentId)
            ->first();

        if ($trainingProgram) {
            $message = $trainingProgram->deleted_at
                ? 'A qualification title with the provided SoC code has been deleted and must be restored before reuse.'
                : 'A qualification title with the provided SoC code already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $trainingProgram = TrainingProgram::withTrashed()
            ->where('title', $data['title'])
            ->where('tvet_id', $data['tvet_id'])
            ->where('priority_id', $data['priority_id'])
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