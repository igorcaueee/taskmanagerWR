<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ClienteController extends Controller
{
    /**
     * Show the clientes (clients) listing / management view.
     */
    public function showClientes(): View
    {
        return view('clientes.home');
    }
}
