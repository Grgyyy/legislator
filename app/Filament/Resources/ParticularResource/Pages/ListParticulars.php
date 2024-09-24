<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use Filament\Actions\Action;
use App\Imports\ParticularImport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ParticularResource;
use App\Models\FundSource;
use App\Models\Particular;
use Exception;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;

class ListParticulars extends ListRecords
{
    protected static string $resource = ParticularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('New'),
            Action::make('ParticularImport')
                ->label('Import')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('attachment'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);
                    try {
                        Excel::import(new ParticularImport, $file);
                        Notification::make()
                            ->title('Import Successful')
                            ->body('Particulars import successful!')
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Particulars import failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    // public function getTabs(): array
    // {
    //     $fundSources = FundSource::all();

    //     $tabs = [];

    //     foreach ($fundSources as $fundSource) {
    //         $tabs[$fundSource->name] = Tab::make()
    //             ->modifyQueryUsing(function ($query) use ($fundSource) {
    //                 $query->whereHas('subParticular', function ($subQuery) use ($fundSource) {
    //                     $subQuery->where('fund_source_id', $fundSource->id);
    //                 });
    //             })
    //             ->badge(function () use ($fundSource) {
    //                 return Particular::whereHas('subParticular', function ($subQuery) use ($fundSource) {
    //                     $subQuery->where('fund_source_id', $fundSource->id);
    //                 })->count();
    //             });
    //     }

    //     return $tabs;
    // }

    public function getTabs(): array
    {
        return [
            'Regional' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 1);
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 1);
                    })->count();
                }),
            'Central' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 2);
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 2);
                    })->count();
                }),
            'District' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 3);
                    })
                    ->where('sub_particular_id', 13);
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 3);
                    })->where('sub_particular_id', 13)->count();
                }),
            'Partylist' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 3);
                    })
                    ->where('sub_particular_id', 14);
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 3);
                    })->where('sub_particular_id', 14)->count();
                }),
            'Legislator Funds (No Area)' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 3);
                    })
                    ->whereIn('sub_particular_id', [
                        15,
                        16,
                        17
                    ]);
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $subQuery->where('fund_source_id', 3);
                    })->whereIn('sub_particular_id', [
                        15,
                        16,
                        17
                    ])->count();
                }),
        ];
    }
}
