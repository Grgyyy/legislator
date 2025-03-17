<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Resources\Resource;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = "USER MANAGEMENT";

    protected static ?string $navigationLabel = 'Activity Logs';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'allocationLogs' => Pages\AllocationLogs::route('/{record}/allocation'),
            'skillPrioLogs' => Pages\SkillPriorityLogs::route('/{record}/skillPriority'),
            'toolkitLogs' => Pages\ToolkitLogs::route('/{record}/toolkit'),
        ];
    }
}
