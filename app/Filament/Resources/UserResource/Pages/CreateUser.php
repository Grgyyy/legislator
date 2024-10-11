<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use App\Filament\Resources\UserResource;
use App\Services\NotificationHandler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string 
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): User
    {
        $this->validateUniqueUser($data);

        $user = DB::transaction(fn() => User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]));

        NotificationHandler::sendSuccessNotification('Created', 'User has been created successfully.');

        return $user;
    }

    protected function validateUniqueUser($data)
    {
        $user = User::withTrashed()
            ->where('email', $data['email'])
            ->first();

        if ($user) {
            $message = $user->deleted_at 
                ? 'This email has been deleted and must be restored before reuse.' 
                : 'This email is already associated with an account.';
            
            NotificationHandler::handleValidationException('Email Already In Use', $message);
        }
    }
}
