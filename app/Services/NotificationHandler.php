<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class NotificationHandler
{
    public static function sendErrorNotification(string $title, string $message)
    {
        Notification::make()
            ->title($title)
            ->body($message)
            ->danger()
            ->duration(7000)
            ->send();
    }

    public static function sendSuccessNotification(string $title, $message)
    {
        Notification::make()
            ->title($title)
            ->body($message)
            ->success()
            ->duration(7000)
            ->send();
    }

    public static function handleValidationException(string $title, string $message)
    {
        self::sendErrorNotification($title, $message);

        throw ValidationException::withMessages(['name' => $message]);
    }
}