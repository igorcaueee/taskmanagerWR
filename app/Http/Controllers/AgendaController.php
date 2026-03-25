<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function showAgenda(Request $request)
    {
        return view('agenda.home');
    }
}
