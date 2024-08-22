<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Filament\Resources\LegislatorResource;
use App\Imports\LegislatorsImport;
use App\Models\Legislator;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListLegislators extends ListRecords
{
    protected static string $resource = LegislatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New')
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make(),
            'Active' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->where('status_id', 1);
                })
                ->badge(function () {
                    return Legislator::where('status_id', 1)->count();
                }),
            'Inactive' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->where('status_id', 2);
                })
                ->badge(function () {
                    return Legislator::where('status_id', 2)->count();
                }),
        ];
    }
}

