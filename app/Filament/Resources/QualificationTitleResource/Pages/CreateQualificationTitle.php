<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Filament\Resources\QualificationTitleResource;
use App\Models\QualificationTitle;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateQualificationTitle extends CreateRecord
{
    protected static string $resource = QualificationTitleResource::class;

    protected static ?string $title = 'Schedule of Cost';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
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

    public function getBreadcrumbs(): array
    {
        return [
            '/schedule-of-cost' => 'Schedule of Cost',
            'Create',
        ];
    }

    protected function handleRecordCreation(array $data): QualificationTitle
    {
        $this->validateUniqueQualificationTitle($data);

        $target = DB::transaction(function () use ($data) {
            $costingFields = [
                'training_cost_pcc',
                'training_support_fund',
                'assessment_fee',
                'entrepreneurship_fee',
                'new_normal_assistance',
                'accident_insurance',
                'book_allowance',
                'uniform_allowance',
                'misc_fee',
            ];

            $costing = collect($costingFields)
                ->mapWithKeys(fn($field) => [$field => $this->ensureNumeric($data[$field])])
                ->toArray();

            $totalPCC = $this->computePCC($costing);

            return QualificationTitle::create(array_merge($costing, [
                'training_program_id' => $data['training_program_id'],
                'scholarship_program_id' => $data['scholarship_program_id'],
                'hours_duration' => $this->ensureNumeric($data['hours_duration']),
                'days_duration' => $this->ensureNumeric($data['days_duration']),
                'pcc' => $totalPCC,
                'soc' => 1
            ]));
        });

        NotificationHandler::sendSuccessNotification('Created', 'Schedule of cost has been created successfully.');

        return $target;
    }

    protected function computePCC(array $costing): float
    {
        return array_sum($costing);
    }

    protected function ensureNumeric($value): float
    {
        return is_numeric($value) ? (float) $value : 0;
    }

    protected function validateUniqueQualificationTitle($data)
    {
        $qualificationTitle = QualificationTitle::withTrashed()
            ->where('training_program_id', $data['training_program_id'])
            ->where('scholarship_program_id', $data['scholarship_program_id'])
            ->first();

        if ($qualificationTitle) {
            $message = $qualificationTitle->deleted_at
                ? 'A schedule of cost associated with the qualification title and scholarship program has been deleted and must be restored before reuse.'
                : 'A schedule of cost associated with the qualification title and scholarship program already exists.';

            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}