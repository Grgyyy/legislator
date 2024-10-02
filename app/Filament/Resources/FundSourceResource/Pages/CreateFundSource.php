<?php

namespace App\Filament\Resources\FundSourceResource\Pages;

use App\Models\FundSource;
use App\Filament\Resources\FundSourceResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateFundSource extends CreateRecord
{
    protected static string $resource = FundSourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): FundSource
    {
        $this->validateUniqueFundSource($data['name']);

        return DB::transaction(fn() => FundSource::create([
            'name' => $data['name'],
        ]));
    }

    protected function validateUniqueFundSource($name)
    {
        $fundSource = FundSource::withTrashed()
            ->where('name', $name)
            ->first();

        if ($fundSource) {
            $message = $fundSource->deleted_at 
                ? 'This fund source has been deleted and must be restored before reuse.' 
                : 'A fund source with this name already exists.';
            
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }
}