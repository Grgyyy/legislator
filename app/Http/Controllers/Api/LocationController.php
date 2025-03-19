<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    //

    public function getPSGC(){
        return response()->json(Region::with(['provinces' => function($q){
            $q->where('name','<>','Not Applicable');
        },'provinces.district.municipality'])->where('name','<>','Not Applicable')->get());
    }
}
