<?php

use App\Exports\TargetReportExport;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\RegionController;
use App\Models\Allocation;
use App\Models\Target;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/export-targets/{allocationId}', function ($allocationId) {
    $allocation = Allocation::find($allocationId);
    
    if (!$allocation || !$allocation->legislator) {
        return redirect()->route('some.error.page')->with('error', 'Invalid Allocation ID or Missing Legislator');
    }

    $legislatorName = preg_replace('/[^A-Za-z0-9-_ ]/', '', $allocation->legislator->name); 
    $filename = now()->format('m-d-Y') . ' - ' . $legislatorName . ' Target Report.xlsx';

    return Excel::download(new TargetReportExport($allocationId), $filename);
})->name('export.targets');


Route::get('/error404', function () {
    abort(404);
});
