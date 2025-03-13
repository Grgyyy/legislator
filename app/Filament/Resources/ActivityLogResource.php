<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\ActivityLogResource\RelationManagers;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
            'allocationLogs' => Pages\AllocationLogs::route('/{record}/edit'),
        ];
    }
}
