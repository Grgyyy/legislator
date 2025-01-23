<?php

namespace App\Filament\Resources\ScholarshipProgramResource\Pages;

use App\Filament\Resources\ScholarshipProgramResource;
use App\Helpers\Helper;
use App\Models\ScholarshipProgram;
use App\Services\NotificationHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateScholarshipProgram extends CreateRecord
{
    protected static string $resource = ScholarshipProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function handleRecordCreation(array $data): ScholarshipProgram
    {
        $this->validateUniqueScholarshipProgram($data);

        $data['name'] = Helper::capitalizeWords($data['name']);
        $data['desc'] = Helper::capitalizeWords($data['desc']);

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
            ->first();

        if ($schoPro) {
            $message = $schoPro->deleted_at 
                ? 'This scholarship program has been deleted and must be restored before reuse.' 
                : 'A scholarship program with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $desc = ScholarshipProgram::withTrashed()
            ->where('desc', $data['desc'])
            ->first();

        if ($desc) {
            $message = $desc->deleted_at
                ? 'This scholarship program with the provided details has been deleted and must be restored before reuse.'
                : 'A scholarship program with the provided details already exists.';

                NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $code = ScholarshipProgram::withTrashed()
            ->where('code', $data)
            ->first();

        if ($code) {
            $message = $code->deleted_at 
                ? 'A scholarship program with this code already exists and has been deleted.' 
                : 'A scholarship program with this code already exists.';
        
            NotificationHandler::handleValidationException('Invalid Code', $message);
        }
    }
}