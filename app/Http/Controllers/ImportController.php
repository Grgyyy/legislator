<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\RegionImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function regionImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:2048',
        ]);

        try {
            Excel::import(new RegionImport, $request->file('file'));
            return response()->json(['message' => 'Regions imported successfully!'], 200);
        } catch (\Exception $e) {
            Log::error('Region Import Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}
