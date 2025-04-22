<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    //

    // public function getPSGC(){
    //     return response()->json(Region::with(['provinces' => function($q){
    //         $q->where('name','<>','Not Applicable');
    //     },'provinces.district.municipality'])->where('name','<>','Not Applicable')->get());
    // }
    public function getPSGC() 
    {
        $regions = Region::with([
            'provinces' => function ($provinceQuery) {
                $provinceQuery->where('name', '!=', 'Not Applicable')
                    ->with([
                        'district' => function ($districtQuery) {
                            $districtQuery->with(['municipality', 'underMunicipality']);
                        },
                        'municipality'
                    ]);
            }
        ])
        ->where('name', '!=', 'Not Applicable')
        ->get();
    
        return RegionResource::collection($regions);
    }
    
}
