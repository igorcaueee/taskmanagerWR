<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TarefaController extends Controller
{
    /**
     * Show the tarefas (tasks) listing / management view.
     */
    public function showTarefas(): View
    {
        return view('tarefas.home');
    }
}
