<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the internal dashboard view.
     */
    public function showDashboard(): View
    {
        $totalUsuariosAtivos = Usuario::query()->where('status', true)->count();

        return view('dashboard', compact('totalUsuariosAtivos'));
    }
}
