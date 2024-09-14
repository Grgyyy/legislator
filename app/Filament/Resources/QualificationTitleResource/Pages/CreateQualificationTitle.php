<?php

namespace App\Filament\Resources\QualificationTitleResource\Pages;

use App\Models\QualificationTitle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\QualificationTitleResource;
use Filament\Notifications\Notification;

class CreateQualificationTitle extends CreateRecord
{
    protected static string $resource = QualificationTitleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): QualificationTitle
    {
        return DB::transaction(function () use ($data) {
            $costing = [
                'training_cost_pcc' => $this->ensureNumeric($data['training_cost_pcc']),
                'cost_of_toolkit_pcc' => $this->ensureNumeric($data['cost_of_toolkit_pcc']),
                'training_support_fund' => $this->ensureNumeric($data['training_support_fund']),
                'assessment_fee' => $this->ensureNumeric($data['assessment_fee']),
                'entrepeneurship_fee' => $this->ensureNumeric($data['entrepeneurship_fee']),
                'new_normal_assisstance' => $this->ensureNumeric($data['new_normal_assisstance']),
                'accident_insurance' => $this->ensureNumeric($data['accident_insurance']),
                'book_allowance' => $this->ensureNumeric($data['book_allowance']),
                'uniform_allowance' => $this->ensureNumeric($data['uniform_allowance']),
                'misc_fee' => $this->ensureNumeric($data['misc_fee']),
            ];

            $totalPCC = $this->computePCC($costing);

            $this->validateUniqueQualificationTitle($data['training_program_id'], $data['scholarship_program_id']);

            $target = QualificationTitle::create(array_merge($costing, [
                'training_program_id' => $data['training_program_id'],
                'scholarship_program_id' => $data['scholarship_program_id'],
                'hours_duration' => $this->ensureNumeric($data['hours_duration']),
                'days_duration' => $this->ensureNumeric($data['days_duration']),
                'pcc' => $totalPCC
            ]));

            return $target;
        });
    }

    protected function computePCC(array $costing): float
    {

        return array_sum($costing);
    }

    protected function ensureNumeric($value): float
    {
        return is_numeric($value) ? (float) $value : 0;
    }

    protected function validateUniqueQualificationTitle($trainingProgramId, $scholarshipProgramId)
    {
        $existingTitle = QualificationTitle::withTrashed()
            ->where('training_program_id', $trainingProgramId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->first();

        if ($existingTitle) {
            $message = $existingTitle->deleted_at
                ? 'A Qualification Title with this Training Program and Scholarship Program exists and is marked as deleted. You cannot create it again.'
                : 'A Qualification Title with this Training Program and Scholarship Program already exists.';

            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'training_program_id' => $message,
            'scholarship_program_id' => $message,
        ]);
    }
}

