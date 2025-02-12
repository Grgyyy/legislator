<?php

namespace App\Http\Controllers;

use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LegislativeTargetReportController extends Controller
{
    public function index()
    {
        if (Gate::denies('view-legislative-targets')) {
            abort(403, 'Unauthorized action.');
        }

        // Fetch and return data if authorized
        $targets = Target::all();
        return view('targets.index', compact('targets'));
    }
}
