<?php

namespace App\Filament\Resources\LegislatorResource\Pages;

use App\Filament\Resources\LegislatorResource;
use App\Imports\LegislatorImport;
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
                ->label('New Legislator'),
            Action::make('importLegislators')
                ->label('Import Legislator')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    Excel::import(new LegislatorImport, $file);
                })
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make(),
            'Active' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->where('status', 'Active');
                })
                ->badge(function () {
                    return Legislator::where('status', 'Active')->count();
                }),
            'Inactive' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->where('status', 'Inactive');
                })
                ->badge(function () {
                    return Legislator::where('status', 'Inactive')->count();
                }),
        ];
    }
}

