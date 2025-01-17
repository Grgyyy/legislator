<?php

use App\Models\Target;
use App\Models\Allocation;
use App\Exports\TargetReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;

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

    return Excel::download(new TargetReportExport($allocationId), 'pending_target_export.xlsx');
})->name('export.targets');
