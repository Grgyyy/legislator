<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Models\ScholarshipProgram;
use App\Filament\Resources\ScholarshipProgramResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateScholarshipProgram extends CreateRecord
{
    protected static string $resource = ScholarshipProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): ScholarshipProgram
    {
        $this->validateUniqueScholarshipProgram($data);

        $schoPro = DB::transaction(fn () => ScholarshipProgram::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'desc' => $data['desc'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'Scholarship program has been created successfully.');

        return $schoPro;
    }

    protected function validateUniqueScholarshipProgram($data)
    {
        $schoPro = ScholarshipProgram::withTrashed()
            ->where('name', $data['name'])
            ->where('desc', $data['desc'])
            ->first();

        if ($schoPro) {
            $message = $schoPro->deleted_at
                ? 'This scholarship program with the provided details has been deleted and must be restored before reuse.'
                : 'A scholarship program with the provided details already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $code = ScholarshipProgram::withTrashed()
            ->where('code', $data)
            ->first();

        if ($code) {
            $message = $code->deleted_at 
                ? 'A Scholarship Program with this code already exists and has been deleted.' 
                : 'A Scholarship Program with this code already exists.';
        
            NotificationHandler::handleValidationException('Invalid Code', $message);
        }
    }
}