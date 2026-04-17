<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\RelatorioController;
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
Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios')->middleware('auth');
Route::get('/relatorios/clientes', [RelatorioController::class, 'clientes'])->name('relatorios.clientes')->middleware('auth');
Route::get('/relatorios/colaboradores', [RelatorioController::class, 'colaboradores'])->name('relatorios.colaboradores')->middleware('auth');
Route::get('/relatorios/produtos', [RelatorioController::class, 'produtos'])->name('relatorios.produtos')->middleware('auth');
Route::get('/agenda', [AgendaController::class, 'showAgenda'])->name('agenda')->middleware('auth');
Route::get('/agenda/compromisso/form', [AgendaController::class, 'formCompromisso'])->name('agenda.compromisso.form')->middleware('auth');
Route::post('/agenda/compromisso', [AgendaController::class, 'storeCompromisso'])->name('agenda.compromisso.store')->middleware('auth');
Route::put('/agenda/compromisso/{id}', [AgendaController::class, 'updateCompromisso'])->name('agenda.compromisso.update')->middleware('auth');
Route::delete('/agenda/compromisso/{id}', [AgendaController::class, 'destroyCompromisso'])->name('agenda.compromisso.destroy')->middleware('auth');
Route::get('/agenda/compromisso/{id}/detalhe', [AgendaController::class, 'detalheCompromisso'])->name('agenda.compromisso.detalhe')->middleware('auth');
Route::get('/agenda/tarefa/{id}/detalhe', [AgendaController::class, 'detalheTarefa'])->name('agenda.tarefa.detalhe')->middleware('auth');
// Clientes routes
Route::get('/clientes', [ClienteController::class, 'showClientes'])->name('clientes')->middleware('auth');
Route::get('/clientes/form', [ClienteController::class, 'formClienteCreate'])->name('clientes.form.create')->middleware('auth');
Route::get('/clientes/{id}/form', [ClienteController::class, 'formClienteEdit'])->name('clientes.form.edit')->middleware('auth');
Route::post('/clientes/save', [ClienteController::class, 'saveCliente'])->name('clientes.save')->middleware('auth');
Route::put('/clientes/{id}', [ClienteController::class, 'updateCliente'])->name('clientes.update')->middleware('auth');
Route::delete('/clientes/{id}', [ClienteController::class, 'deleteCliente'])->name('clientes.delete')->middleware('auth');
// Produtos routes
Route::get('/produtos', [ProdutoController::class, 'showProdutos'])->name('produtos')->middleware('auth');
Route::get('/produtos/form', [ProdutoController::class, 'formCreate'])->name('produtos.form.create')->middleware('auth');
Route::get('/produtos/{id}/form', [ProdutoController::class, 'formEdit'])->name('produtos.form.edit')->middleware('auth');
Route::post('/produtos/save', [ProdutoController::class, 'save'])->name('produtos.save')->middleware('auth');
Route::put('/produtos/{id}', [ProdutoController::class, 'update'])->name('produtos.update')->middleware('auth');
Route::delete('/produtos/{id}', [ProdutoController::class, 'delete'])->name('produtos.delete')->middleware('auth');
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
// Arquivos routes
Route::get('/arquivos', [FileExplorerController::class, 'index'])->name('arquivos')->middleware('auth');
Route::get('/arquivos/download', [FileExplorerController::class, 'download'])->name('arquivos.download')->middleware('auth');
Route::post('/arquivos/upload', [FileExplorerController::class, 'upload'])->name('arquivos.upload')->middleware('auth');
Route::post('/arquivos/folder', [FileExplorerController::class, 'createFolder'])->name('arquivos.createFolder')->middleware('auth');
Route::put('/arquivos/rename', [FileExplorerController::class, 'rename'])->name('arquivos.rename')->middleware('auth');
Route::delete('/arquivos/delete', [FileExplorerController::class, 'delete'])->name('arquivos.delete')->middleware('auth');
