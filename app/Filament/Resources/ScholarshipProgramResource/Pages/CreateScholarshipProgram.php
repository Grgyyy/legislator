<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\ScholarshipProgramResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateScholarshipProgram extends CreateRecord
{
    protected static string $resource = ScholarshipProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): ScholarshipProgram
    {
        return DB::transaction(function () use ($data) {
            $this->validateUniqueScholarshipProgram($data['code']);

            return ScholarshipProgram::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'desc' => $data['desc'],
            ]);
        });
    }

    protected function validateUniqueScholarshipProgram($code)
    {
        $query = ScholarshipProgram::withTrashed()
            ->where('code', $code)
            ->first();

        if ($query) {
            if ($query->deleted_at) {
                $message = 'Scholarship Program with this code exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'Scholarship Program with this code already exists.';
            }
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
            'code' => $message,
        ]);
    }
}
