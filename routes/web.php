<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ClienteConhecimentoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\FunilController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\LeadCapturaController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SegmentacaoController;
use App\Http\Controllers\TarefaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public lead capture form (no auth required)
Route::get('/funil/captura', [LeadCapturaController::class, 'showForm'])->name('funil.captura');
Route::post('/funil/captura', [LeadCapturaController::class, 'store'])->name('funil.captura.store')->middleware('throttle:10,1');

// Colabs routes (use GET for page views so they load in browser)
Route::get('/dashboard', [DashboardController::class, 'showDashboard'])->name('dashboard')->middleware('auth');
Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios')->middleware('auth');
Route::get('/relatorios/clientes', [RelatorioController::class, 'clientes'])->name('relatorios.clientes')->middleware('auth');
Route::get('/relatorios/colaboradores', [RelatorioController::class, 'colaboradores'])->name('relatorios.colaboradores')->middleware(['auth', 'colaboradores']);
Route::get('/relatorios/produtos', [RelatorioController::class, 'produtos'])->name('relatorios.produtos')->middleware('auth');
Route::get('/relatorios/geolocalizacao', [RelatorioController::class, 'geolocalizacao'])->name('relatorios.geolocalizacao')->middleware('auth');
Route::get('/agenda', [AgendaController::class, 'showAgenda'])->name('agenda')->middleware('auth');
Route::get('/agenda/compromisso/form', [AgendaController::class, 'formCompromisso'])->name('agenda.compromisso.form')->middleware('auth');
Route::post('/agenda/compromisso', [AgendaController::class, 'storeCompromisso'])->name('agenda.compromisso.store')->middleware('auth');
Route::put('/agenda/compromisso/{id}', [AgendaController::class, 'updateCompromisso'])->name('agenda.compromisso.update')->middleware('auth');
Route::delete('/agenda/compromisso/{id}', [AgendaController::class, 'destroyCompromisso'])->name('agenda.compromisso.destroy')->middleware('auth');
Route::get('/agenda/compromisso/{id}/detalhe', [AgendaController::class, 'detalheCompromisso'])->name('agenda.compromisso.detalhe')->middleware('auth');
Route::get('/agenda/tarefa/{id}/detalhe', [AgendaController::class, 'detalheTarefa'])->name('agenda.tarefa.detalhe')->middleware('auth');

// Google Calendar OAuth & sync
Route::get('/google/connect', [GoogleCalendarController::class, 'redirect'])->name('google.calendar.redirect')->middleware('auth');
Route::get('/google/callback', [GoogleCalendarController::class, 'callback'])->name('google.calendar.callback')->middleware('auth');
Route::post('/google/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google.calendar.disconnect')->middleware('auth');
Route::post('/google/sync', [GoogleCalendarController::class, 'sync'])->name('google.calendar.sync')->middleware('auth');
// Funil de Vendas (CRM) routes
Route::get('/funil', [FunilController::class, 'showFunil'])->name('funil')->middleware(['auth', 'diretor']);
Route::get('/leads/form', [FunilController::class, 'formCreate'])->name('leads.form.create')->middleware(['auth', 'diretor']);
Route::get('/leads/{id}/form', [FunilController::class, 'formEdit'])->name('leads.form.edit')->middleware(['auth', 'diretor']);
Route::post('/leads/save', [FunilController::class, 'save'])->name('leads.save')->middleware(['auth', 'diretor']);
Route::put('/leads/{id}', [FunilController::class, 'update'])->name('leads.update')->middleware(['auth', 'diretor']);
Route::patch('/leads/{id}/etapa', [FunilController::class, 'updateEtapa'])->name('leads.update.etapa')->middleware(['auth', 'diretor']);
Route::get('/leads/{id}/detalhe', [FunilController::class, 'detalhe'])->name('leads.detalhe')->middleware(['auth', 'diretor']);
Route::get('/leads/{id}/form-conversao', [FunilController::class, 'formConversao'])->name('leads.form.conversao')->middleware(['auth', 'diretor']);
Route::post('/leads/{id}/converter', [FunilController::class, 'converterParaCliente'])->name('leads.converter')->middleware(['auth', 'diretor']);
Route::delete('/leads/{id}', [FunilController::class, 'delete'])->name('leads.delete')->middleware(['auth', 'diretor']);
// Clientes routes
Route::get('/clientes', [ClienteController::class, 'showClientes'])->name('clientes')->middleware('auth');
Route::get('/clientes/form', [ClienteController::class, 'formClienteCreate'])->name('clientes.form.create')->middleware('auth');
Route::get('/clientes/busca', [ClienteController::class, 'busca'])->name('clientes.busca')->middleware('auth');
Route::get('/clientes/{id}/detalhe', [ClienteController::class, 'showCliente'])->name('clientes.show')->middleware('auth');
Route::get('/clientes/import/form', [ClienteController::class, 'formImportClientes'])->name('clientes.import.form')->middleware('auth');
Route::get('/clientes/import/template', [ClienteController::class, 'templateClientes'])->name('clientes.import.template')->middleware('auth');
Route::post('/clientes/import', [ClienteController::class, 'importClientes'])->name('clientes.import')->middleware('auth');
Route::post('/clientes/save', [ClienteController::class, 'saveCliente'])->name('clientes.save')->middleware('auth');
Route::get('/clientes/{id}/encerrar', [ClienteController::class, 'formEncerrarCliente'])->name('clientes.form.encerrar')->middleware('auth');
Route::post('/clientes/{id}/encerrar', [ClienteController::class, 'encerrarCliente'])->name('clientes.encerrar')->middleware('auth');
Route::post('/clientes/{id}/reativar', [ClienteController::class, 'reativarCliente'])->name('clientes.reativar')->middleware('auth');
Route::get('/clientes/{id}/form', [ClienteController::class, 'formClienteEdit'])->name('clientes.form.edit')->middleware('auth');
Route::put('/clientes/{id}', [ClienteController::class, 'updateCliente'])->name('clientes.update')->middleware('auth');
Route::delete('/clientes/{id}', [ClienteController::class, 'deleteCliente'])->name('clientes.delete')->middleware('auth');
// Quadro societário routes
Route::get('/clientes/{id}/quadro-societario', [ClienteController::class, 'quadroSocietario'])->name('clientes.quadro.modal')->middleware('auth');
// Conhecimentos IA por cliente
Route::get('/clientes/{id}/conhecimentos', [ClienteConhecimentoController::class, 'modal'])->name('clientes.conhecimentos.modal')->middleware('auth');
Route::get('/clientes/{id}/conhecimentos/form', [ClienteConhecimentoController::class, 'formCreate'])->name('clientes.conhecimentos.form.create')->middleware('auth');
Route::post('/clientes/{id}/conhecimentos', [ClienteConhecimentoController::class, 'store'])->name('clientes.conhecimentos.store')->middleware('auth');
Route::get('/conhecimentos/{id}/form', [ClienteConhecimentoController::class, 'formEdit'])->name('conhecimentos.form.edit')->middleware('auth');
Route::put('/conhecimentos/{id}', [ClienteConhecimentoController::class, 'update'])->name('conhecimentos.update')->middleware('auth');
Route::delete('/conhecimentos/{id}', [ClienteConhecimentoController::class, 'destroy'])->name('conhecimentos.destroy')->middleware('auth');
Route::post('/clientes/{id}/socios', [ClienteController::class, 'saveSocio'])->name('clientes.socios.save')->middleware('auth');
Route::put('/clientes/socios/{id}', [ClienteController::class, 'updateSocio'])->name('clientes.socios.update')->middleware('auth');
Route::delete('/clientes/socios/{id}', [ClienteController::class, 'deleteSocio'])->name('clientes.socios.delete')->middleware('auth');
// Segmentações routes
Route::post('/segmentacoes', [SegmentacaoController::class, 'store'])->name('segmentacoes.store')->middleware('auth');
// Produtos routes
Route::get('/produtos', [ProdutoController::class, 'showProdutos'])->name('produtos')->middleware('auth');
Route::get('/produtos/form', [ProdutoController::class, 'formCreate'])->name('produtos.form.create')->middleware('auth');
Route::get('/produtos/{id}/form', [ProdutoController::class, 'formEdit'])->name('produtos.form.edit')->middleware('auth');
Route::post('/produtos/save', [ProdutoController::class, 'save'])->name('produtos.save')->middleware('auth');
Route::post('/produtos/inline', [ProdutoController::class, 'storeInline'])->name('produtos.store.inline')->middleware('auth');
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
// Colaboradores routes (diretor, TI, supervisor)
Route::get('/colaboradores', [UsuarioController::class, 'showColaboradores'])->name('colaboradores')->middleware(['auth', 'colaboradores']);
Route::get('/colaboradores/form', [UsuarioController::class, 'formColabCreate'])->name('colaboradores.form.create')->middleware(['auth', 'colaboradores']);
Route::get('/colaboradores/{id}/form', [UsuarioController::class, 'formColabEdit'])->name('colaboradores.form.edit')->middleware(['auth', 'colaboradores']);
Route::post('/colaboradores/save', [UsuarioController::class, 'saveColab'])->name('colaboradores.save')->middleware(['auth', 'colaboradores']);
Route::put('/colaboradores/{id}', [UsuarioController::class, 'updateColab'])->name('colaboradores.update')->middleware(['auth', 'colaboradores']);
Route::delete('/colaboradores/{id}', [UsuarioController::class, 'deleteColab'])->name('colaboradores.delete')->middleware(['auth', 'colaboradores']);
// Arquivos routes
Route::get('/arquivos', [FileExplorerController::class, 'index'])->name('arquivos')->middleware('auth');
Route::get('/arquivos/download', [FileExplorerController::class, 'download'])->name('arquivos.download')->middleware('auth');
Route::post('/arquivos/upload', [FileExplorerController::class, 'upload'])->name('arquivos.upload')->middleware('auth');
Route::post('/arquivos/folder', [FileExplorerController::class, 'createFolder'])->name('arquivos.createFolder')->middleware('auth');
Route::put('/arquivos/rename', [FileExplorerController::class, 'rename'])->name('arquivos.rename')->middleware('auth');
Route::delete('/arquivos/delete', [FileExplorerController::class, 'delete'])->name('arquivos.delete')->middleware('auth');
// Chatbot routes
Route::post('/chatbot/mensagem', [ChatbotController::class, 'message'])->name('chatbot.message')->middleware('auth');
Route::delete('/chatbot/historico', [ChatbotController::class, 'clear'])->name('chatbot.clear')->middleware('auth');
