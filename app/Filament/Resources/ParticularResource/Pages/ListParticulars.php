<?php

namespace App\Filament\Resources\ParticularResource\Pages;

use App\Models\SubParticular;
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
                        $regionalRecord = FundSource::where('name', 'RO Regular')->first();
                        $regionalId = $regionalRecord->id;
                        $subQuery->where('fund_source_id', $regionalId);
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $regionalRecord = FundSource::where('name', 'RO Regular')->first();
                        $regionalId = $regionalRecord->id;
                        $subQuery->where('fund_source_id', $regionalId);
                    })->count();
                }),
            'Central' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $centralRecord = FundSource::where('name', 'CO Regular')->first();
                        $centralId = $centralRecord->id;
                        $subQuery->where('fund_source_id', $centralId);
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $centralRecord = FundSource::where('name', 'CO Regular')->first();
                        $centralId = $centralRecord->id;
                        $subQuery->where('fund_source_id', $centralId);
                    })->count();
                }),
            'District' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->where('name', 'District');
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->where('name', 'District');
                    })->count();
                }),
            'Partylist' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->where('name', 'Partylist');
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->where('name', 'Partylist');
                    })->count();
                }),
            'House Speaker' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->whereIn('name', ['House Speaker', 'House Speaker (LAKAS)']);
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->whereIn('name', ['House Speaker', 'House Speaker (LAKAS)']);
                    })->count();
                }),
            'Senator' => Tab::make()
                ->modifyQueryUsing(function ($query) {
                    $query->whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->where('name', 'Senator');
                    });
                })
                ->badge(function () {
                    return Particular::whereHas('subParticular', function ($subQuery) {
                        $LegisRecord = FundSource::where('name', 'CO Legislator Funds')->first();
                        $LegisId = $LegisRecord->id;
                        $subQuery->where('fund_source_id', $LegisId)
                            ->where('name', 'Senator');
                    })->count();
                }),
        ];
    }
}
