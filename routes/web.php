<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TarefaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {return view('welcome');});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Colabs routes (use GET for page views so they load in browser)
Route::get('/dashboard', [DashboardController::class, 'showDashboard'])->name('dashboard')->middleware('auth');
Route::get('/agenda', [AgendaController::class, 'showAgenda'])->name('agenda')->middleware('auth');
Route::get('/clientes', [ClienteController::class, 'showClientes'])->name('clientes')->middleware('auth');
Route::get('/tarefas', [TarefaController::class, 'showTarefas'])->name('tarefas')->middleware('auth');
Route::get('/colaboradores', [UsuarioController::class, 'showColaboradores'])->name('colaboradores')->middleware('auth');
Route::get('/colaboradores/form', [UsuarioController::class, 'formColabCreate'])->name('colaboradores.form.create')->middleware('auth');
Route::get('/colaboradores/{id}/form', [UsuarioController::class, 'formColabEdit'])->name('colaboradores.form.edit')->middleware('auth');
Route::post('/colaboradores/save', [UsuarioController::class, 'saveColab'])->name('colaboradores.save')->middleware('auth');
Route::put('/colaboradores/{id}', [UsuarioController::class, 'updateColab'])->name('colaboradores.update')->middleware('auth');
Route::delete('/colaboradores/{id}', [UsuarioController::class, 'deleteColab'])->name('colaboradores.delete')->middleware('auth');
