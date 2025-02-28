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
    if (!$allocation) {
        return redirect()->route('some.error.page')->with('error', 'Invalid Allocation ID');
    }

    return Excel::download(new TargetReportExport($allocationId), 'Target Report Export.xlsx');
})->name('export.targets');

