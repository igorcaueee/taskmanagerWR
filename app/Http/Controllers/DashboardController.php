<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the internal dashboard view.
     */
    public function showDashboard(): View
    {
        return view('dashboard');
    }
}
