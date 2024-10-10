<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Sectors extends Cluster
{
    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?int $navigationSort = 5;
}
