<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TarefaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Colabs routes (use GET for page views so they load in browser)
Route::get('/dashboard', [DashboardController::class, 'showDashboard'])->name('dashboard')->middleware('auth');
Route::get('/agenda', [AgendaController::class, 'showAgenda'])->name('agenda')->middleware('auth');
// Clientes routes
Route::get('/clientes', [ClienteController::class, 'showClientes'])->name('clientes')->middleware('auth');
Route::get('/clientes/form', [ClienteController::class, 'formClienteCreate'])->name('clientes.form.create')->middleware('auth');
Route::get('/clientes/{id}/form', [ClienteController::class, 'formClienteEdit'])->name('clientes.form.edit')->middleware('auth');
Route::post('/clientes/save', [ClienteController::class, 'saveCliente'])->name('clientes.save')->middleware('auth');
Route::put('/clientes/{id}', [ClienteController::class, 'updateCliente'])->name('clientes.update')->middleware('auth');
Route::delete('/clientes/{id}', [ClienteController::class, 'deleteCliente'])->name('clientes.delete')->middleware('auth');
// Tarefas routes
Route::get('/tarefas', [TarefaController::class, 'showTarefas'])->name('tarefas')->middleware('auth');
Route::get('/tarefaslist', [TarefaController::class, 'showTarefasList'])->name('tarefas.list')->middleware('auth');
Route::get('/tarefas/form', [TarefaController::class, 'formCreate'])->name('tarefas.form.create')->middleware('auth');
Route::get('/tarefas/{id}/form', [TarefaController::class, 'formEdit'])->name('tarefas.form.edit')->middleware('auth');
Route::post('/tarefas/save', [TarefaController::class, 'save'])->name('tarefas.save')->middleware('auth');
Route::put('/tarefas/{id}', [TarefaController::class, 'update'])->name('tarefas.update')->middleware('auth');
Route::get('/tarefas/{id}/detalhe', [TarefaController::class, 'detalhe'])->name('tarefas.detalhe')->middleware('auth');
Route::patch('/tarefas/{id}/etapa', [TarefaController::class, 'updateEtapa'])->name('tarefas.update.etapa')->middleware('auth');
Route::patch('/tarefas/{id}/ciclo/proximo', [TarefaController::class, 'passarParaProximoCiclo'])->name('tarefas.ciclo.proximo')->middleware('auth');
Route::delete('/tarefas/{id}', [TarefaController::class, 'delete'])->name('tarefas.delete')->middleware('auth');
// Colaboradores routes/
Route::get('/colaboradores', [UsuarioController::class, 'showColaboradores'])->name('colaboradores')->middleware('auth');
Route::get('/colaboradores/form', [UsuarioController::class, 'formColabCreate'])->name('colaboradores.form.create')->middleware('auth');
Route::get('/colaboradores/{id}/form', [UsuarioController::class, 'formColabEdit'])->name('colaboradores.form.edit')->middleware('auth');
Route::post('/colaboradores/save', [UsuarioController::class, 'saveColab'])->name('colaboradores.save')->middleware('auth');
Route::put('/colaboradores/{id}', [UsuarioController::class, 'updateColab'])->name('colaboradores.update')->middleware('auth');
Route::delete('/colaboradores/{id}', [UsuarioController::class, 'deleteColab'])->name('colaboradores.delete')->middleware('auth');
