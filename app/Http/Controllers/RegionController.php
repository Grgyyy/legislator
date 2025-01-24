<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\RegionImport;
use Maatwebsite\Excel\Facades\Excel;

class RegionController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new RegionImport, $request->file('file'));
            return back()->with('success', 'Regions imported successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'There was an issue importing the file: ' . $e->getMessage()]);
        }
    }
}
