<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\QuestionarioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\ClienteConhecimentoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailCampanhaController;
use App\Http\Controllers\FileExplorerController;
use App\Http\Controllers\FunilController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\IdeiaController;
use App\Http\Controllers\LeadCapturaController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PossibilidadeController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SegmentacaoController;
use App\Http\Controllers\TarefaController;
use App\Http\Controllers\TipoTarefaController;
use App\Http\Controllers\AcessoExternoController;
use App\Http\Controllers\NfeController;
use App\Http\Controllers\NfseController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Portal do Cliente ────────────────────────────────────────────────────────
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');

    Route::middleware('portal.auth')->group(function () {
        Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/blog', [PortalController::class, 'blog'])->name('blog');
        Route::get('/blog/{slug}', [PortalController::class, 'artigoShow'])->name('blog.show');
        Route::get('/arquivos', [PortalController::class, 'arquivos'])->name('arquivos');
        Route::get('/arquivos/download', [PortalController::class, 'downloadArquivo'])->name('arquivos.download');
        Route::get('/arquivos/visualizar', [PortalController::class, 'visualizarArquivo'])->name('arquivos.visualizar');
        Route::post('/arquivos/{upload}/marcar-pago', [PortalController::class, 'marcarPago'])->name('arquivos.marcar-pago');
        Route::get('/agenda', [PortalController::class, 'agenda'])->name('agenda');
    });
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
Route::get('/leads', [FunilController::class, 'leadsIndex'])->name('leads.index')->middleware(['auth', 'diretor']);
Route::get('/leads/form', [FunilController::class, 'formCreate'])->name('leads.form.create')->middleware(['auth', 'diretor']);
Route::get('/leads/{id}/form', [FunilController::class, 'formEdit'])->name('leads.form.edit')->middleware(['auth', 'diretor']);
Route::post('/leads/save', [FunilController::class, 'save'])->name('leads.save')->middleware(['auth', 'diretor']);
Route::put('/leads/{id}', [FunilController::class, 'update'])->name('leads.update')->middleware(['auth', 'diretor']);
Route::patch('/leads/{id}/etapa', [FunilController::class, 'updateEtapa'])->name('leads.update.etapa')->middleware(['auth', 'diretor']);
Route::get('/leads/{id}/detalhe', [FunilController::class, 'detalhe'])->name('leads.detalhe')->middleware(['auth', 'diretor']);
Route::get('/leads/{id}/form-conversao', [FunilController::class, 'formConversao'])->name('leads.form.conversao')->middleware(['auth', 'diretor']);
Route::post('/leads/{id}/converter', [FunilController::class, 'converterParaCliente'])->name('leads.converter')->middleware(['auth', 'diretor']);
Route::get('/leads/empresas', [FunilController::class, 'searchEmpresas'])->name('leads.empresas')->middleware(['auth', 'diretor']);
Route::get('/leads/{id}', [FunilController::class, 'show'])->name('leads.show')->middleware(['auth', 'diretor']);
Route::delete('/leads/{id}', [FunilController::class, 'delete'])->name('leads.delete')->middleware(['auth', 'diretor']);
Route::post('/leads/{id}/delegar-tarefa', [FunilController::class, 'delegarTarefa'])->name('leads.delegar.tarefa')->middleware(['auth', 'diretor']);
// Clientes routes
Route::get('/clientes', [ClienteController::class, 'showClientes'])->name('clientes')->middleware('auth');
Route::get('/clientes/form', [ClienteController::class, 'formClienteCreate'])->name('clientes.form.create')->middleware('auth');
Route::get('/clientes/busca', [ClienteController::class, 'busca'])->name('clientes.busca')->middleware('auth');
Route::get('/clientes/{id}/detalhe', [ClienteController::class, 'showCliente'])->name('clientes.show')->middleware('auth');
Route::get('/clientes/import/form', [ClienteController::class, 'formImportClientes'])->name('clientes.import.form')->middleware('auth');
Route::get('/clientes/import/template', [ClienteController::class, 'templateClientes'])->name('clientes.import.template')->middleware('auth');
Route::get('/clientes/export', [ClienteController::class, 'exportClientes'])->name('clientes.export')->middleware('auth');
Route::post('/clientes/import', [ClienteController::class, 'importClientes'])->name('clientes.import')->middleware('auth');
Route::post('/clientes/save', [ClienteController::class, 'saveCliente'])->name('clientes.save')->middleware('auth');
Route::get('/clientes/{id}/encerrar', [ClienteController::class, 'formEncerrarCliente'])->name('clientes.form.encerrar')->middleware('auth');
Route::post('/clientes/{id}/encerrar', [ClienteController::class, 'encerrarCliente'])->name('clientes.encerrar')->middleware('auth');
Route::post('/clientes/{id}/reativar', [ClienteController::class, 'reativarCliente'])->name('clientes.reativar')->middleware('auth');
Route::get('/clientes/{id}/form', [ClienteController::class, 'formClienteEdit'])->name('clientes.form.edit')->middleware('auth');
Route::put('/clientes/{id}', [ClienteController::class, 'updateCliente'])->name('clientes.update')->middleware('auth');
Route::delete('/clientes/{id}', [ClienteController::class, 'deleteCliente'])->name('clientes.delete')->middleware('auth');
Route::post('/clientes/{id}/logo', [ClienteController::class, 'uploadLogo'])->name('clientes.logo.upload')->middleware('auth');
Route::delete('/clientes/{id}/logo', [ClienteController::class, 'removeLogo'])->name('clientes.logo.remove')->middleware('auth');
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
// Portal do cliente — gerenciamento de usuários pelo admin
Route::get('/clientes/{id}/portal/pastas', [ClienteController::class, 'pastasPortalCliente'])->name('clientes.portal.pastas')->middleware('auth');
Route::post('/clientes/{id}/portal/usuarios', [ClienteController::class, 'storeUsuarioPortal'])->name('clientes.portal.usuarios.store')->middleware('auth');
Route::put('/clientes/{clienteId}/portal/usuarios/{usuarioId}', [ClienteController::class, 'updateUsuarioPortal'])->name('clientes.portal.usuarios.update')->middleware('auth');
Route::delete('/clientes/{clienteId}/portal/usuarios/{usuarioId}', [ClienteController::class, 'destroyUsuarioPortal'])->name('clientes.portal.usuarios.destroy')->middleware('auth');
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

Route::get('/possibilidades', [PossibilidadeController::class, 'showPossibilidades'])->name('possibilidades')->middleware('auth');
Route::get('/possibilidades/form', [PossibilidadeController::class, 'formCreate'])->name('possibilidades.form.create')->middleware('auth');
Route::get('/possibilidades/{id}/form', [PossibilidadeController::class, 'formEdit'])->name('possibilidades.form.edit')->middleware('auth');
Route::post('/possibilidades/save', [PossibilidadeController::class, 'save'])->name('possibilidades.save')->middleware('auth');
Route::post('/possibilidades/inline', [PossibilidadeController::class, 'storeInline'])->name('possibilidades.store.inline')->middleware('auth');
Route::put('/possibilidades/{id}', [PossibilidadeController::class, 'update'])->name('possibilidades.update')->middleware('auth');
Route::delete('/possibilidades/{id}', [PossibilidadeController::class, 'delete'])->name('possibilidades.delete')->middleware('auth');
// Tipos de Tarefa routes
Route::get('/tipos-tarefa', [TipoTarefaController::class, 'index'])->name('tipos-tarefa.index')->middleware('auth');
Route::get('/tipos-tarefa/form', [TipoTarefaController::class, 'formCreate'])->name('tipos-tarefa.form.create')->middleware('auth');
Route::get('/tipos-tarefa/{id}/form', [TipoTarefaController::class, 'formEdit'])->name('tipos-tarefa.form.edit')->middleware('auth');
Route::post('/tipos-tarefa/save', [TipoTarefaController::class, 'save'])->name('tipos-tarefa.save')->middleware('auth');
Route::put('/tipos-tarefa/{id}', [TipoTarefaController::class, 'update'])->name('tipos-tarefa.update')->middleware('auth');
Route::delete('/tipos-tarefa/{id}', [TipoTarefaController::class, 'delete'])->name('tipos-tarefa.delete')->middleware('auth');
// Tarefas routes
Route::get('/tarefas', [TarefaController::class, 'showTarefas'])->name('tarefas')->middleware('auth');
Route::get('/tarefaslist', [TarefaController::class, 'showTarefasList'])->name('tarefas.list')->middleware('auth');
Route::get('/tarefas/form', [TarefaController::class, 'formCreate'])->name('tarefas.form.create')->middleware('auth');
Route::get('/tarefas/{id}/form', [TarefaController::class, 'formEdit'])->name('tarefas.form.edit')->middleware('auth');
Route::post('/tarefas/save', [TarefaController::class, 'save'])->name('tarefas.save')->middleware('auth');
Route::post('/tarefas/check-duplicata', [TarefaController::class, 'checkDuplicata'])->name('tarefas.check-duplicata')->middleware('auth');
Route::put('/tarefas/{id}', [TarefaController::class, 'update'])->name('tarefas.update')->middleware('auth');
Route::get('/tarefas/{id}/detalhe', [TarefaController::class, 'detalhe'])->name('tarefas.detalhe')->middleware('auth');
Route::patch('/tarefas/{id}/etapa', [TarefaController::class, 'updateEtapa'])->name('tarefas.update.etapa')->middleware('auth');
Route::patch('/tarefas/{id}/ciclo/proximo', [TarefaController::class, 'passarParaProximoCiclo'])->name('tarefas.ciclo.proximo')->middleware('auth');
Route::delete('/tarefas/{id}', [TarefaController::class, 'delete'])->name('tarefas.delete')->middleware('auth');
Route::delete('/tarefas-inativas/excluir-todas', [TarefaController::class, 'deleteAllInativas'])->name('tarefas.delete-all-inativas')->middleware('auth');
Route::delete('/tarefas-duplicadas/excluir-todas', [TarefaController::class, 'deletarDuplicatas'])->name('tarefas.delete-duplicatas')->middleware('auth');
Route::get('/tarefas-duplicadas/contar', [TarefaController::class, 'contarDuplicatas'])->name('tarefas.count-duplicatas')->middleware('auth');
Route::post('/tarefas/{id}/inativar', [TarefaController::class, 'inativar'])->name('tarefas.inativar')->middleware('auth');
Route::post('/tarefas/{id}/ativar', [TarefaController::class, 'ativar'])->name('tarefas.ativar')->middleware('auth');
Route::post('/tarefas/{id}/duplicar', [TarefaController::class, 'duplicar'])->name('tarefas.duplicar')->middleware('auth');
Route::post('/tarefas/{id}/renovar-recorrencia', [TarefaController::class, 'renovarRecorrencia'])->name('tarefas.renovar-recorrencia')->middleware('auth');
Route::post('/tarefas/{id}/upload', [TarefaController::class, 'uploadArquivo'])->name('tarefas.upload')->middleware('auth');
Route::get('/tarefas/uploads-portal', [TarefaController::class, 'uploadsPortal'])->name('tarefas.uploads-portal')->middleware('auth');
Route::get('/tarefas/uploads-portal/{upload}/historico', [TarefaController::class, 'uploadsHistorico'])->name('tarefas.uploads-portal.historico')->middleware('auth');
Route::delete('/tarefas/uploads-portal/{upload}', [TarefaController::class, 'destroyUpload'])->name('tarefas.uploads-portal.destroy')->middleware('auth');
// Colaboradores routes (diretor, TI, supervisor)
Route::get('/colaboradores', [UsuarioController::class, 'showColaboradores'])->name('colaboradores')->middleware(['auth', 'colaboradores']);
Route::get('/colaboradores/form', [UsuarioController::class, 'formColabCreate'])->name('colaboradores.form.create')->middleware(['auth', 'colaboradores.edit']);
Route::get('/colaboradores/{id}/form', [UsuarioController::class, 'formColabEdit'])->name('colaboradores.form.edit')->middleware(['auth', 'colaboradores.edit']);
Route::post('/colaboradores/save', [UsuarioController::class, 'saveColab'])->name('colaboradores.save')->middleware(['auth', 'colaboradores.edit']);
Route::put('/colaboradores/{id}', [UsuarioController::class, 'updateColab'])->name('colaboradores.update')->middleware(['auth', 'colaboradores.edit']);
Route::delete('/colaboradores/{id}', [UsuarioController::class, 'deleteColab'])->name('colaboradores.delete')->middleware(['auth', 'colaboradores.edit']);
// Arquivos routes
Route::get('/arquivos', [FileExplorerController::class, 'index'])->name('arquivos')->middleware('auth');
Route::get('/arquivos/download', [FileExplorerController::class, 'download'])->name('arquivos.download')->middleware('auth');
Route::get('/arquivos/download-publico', [FileExplorerController::class, 'downloadPublico'])->name('arquivos.downloadPublico');
Route::post('/arquivos/upload', [FileExplorerController::class, 'upload'])->name('arquivos.upload')->middleware('auth');
Route::post('/arquivos/folder', [FileExplorerController::class, 'createFolder'])->name('arquivos.createFolder')->middleware('auth');
Route::put('/arquivos/rename', [FileExplorerController::class, 'rename'])->name('arquivos.rename')->middleware('auth');
Route::delete('/arquivos/delete', [FileExplorerController::class, 'delete'])->name('arquivos.delete')->middleware('auth');
Route::post('/arquivos/gerar-link', [FileExplorerController::class, 'gerarLinkPublico'])->name('arquivos.gerarLink')->middleware('auth');
Route::post('/arquivos/enviar-email', [FileExplorerController::class, 'enviarEmail'])->name('arquivos.enviarEmail')->middleware('auth');
Route::get('/arquivos/usuarios', [FileExplorerController::class, 'usuariosParaCompartilhar'])->name('arquivos.usuarios')->middleware('auth');
Route::get('/arquivos/portal-usuarios/{clienteId}', [FileExplorerController::class, 'portalUsuariosDoCliente'])->name('arquivos.portalUsuarios')->middleware('auth');
// Chatbot routes
Route::post('/chatbot/mensagem', [ChatbotController::class, 'message'])->name('chatbot.message')->middleware('auth');
Route::delete('/chatbot/historico', [ChatbotController::class, 'clear'])->name('chatbot.clear')->middleware('auth');
// Notificações
Route::middleware('auth')->prefix('notificacoes')->name('notificacoes.')->group(function () {
    Route::get('/', [NotificacaoController::class, 'index'])->name('index');
    Route::patch('/{id}/lida', [NotificacaoController::class, 'marcarLida'])->name('marcar-lida');
    Route::patch('/marcar-todas-lidas', [NotificacaoController::class, 'marcarTodasLidas'])->name('marcar-todas-lidas');
});
// Versão do build (usado para avisar o usuário sobre atualizações do sistema)
Route::get('/api/system/version', fn () => response()->json(['version' => appBuildVersion()]))->name('system.version')->middleware('auth');
// Blog admin (interno)
Route::get('/admin/blog', [BlogController::class, 'index'])->name('blog.admin.index')->middleware(['auth', 'admin']);
Route::get('/admin/blog/criar', [BlogController::class, 'formCreate'])->name('blog.admin.form.create')->middleware(['auth', 'admin']);
Route::get('/admin/blog/{id}/editar', [BlogController::class, 'formEdit'])->name('blog.admin.form.edit')->middleware(['auth', 'admin']);
Route::post('/admin/blog', [BlogController::class, 'save'])->name('blog.admin.save')->middleware(['auth', 'admin']);
Route::put('/admin/blog/{id}', [BlogController::class, 'update'])->name('blog.admin.update')->middleware(['auth', 'admin']);
Route::delete('/admin/blog/{id}', [BlogController::class, 'delete'])->name('blog.admin.delete')->middleware(['auth', 'admin']);
Route::post('/admin/blog/gerar-ia', [BlogController::class, 'gerarIA'])->name('blog.admin.gerar-ia')->middleware(['auth', 'admin']);
// Email Campanhas (Newsletter)
Route::get('/email-campanhas', [EmailCampanhaController::class, 'index'])->name('email-campanhas.index')->middleware(['auth', 'email-marketing']);
Route::get('/email-campanhas/criar', [EmailCampanhaController::class, 'create'])->name('email-campanhas.create')->middleware(['auth', 'email-marketing']);
Route::post('/email-campanhas/gerar-ia', [EmailCampanhaController::class, 'gerarConteudoAi'])->name('email-campanhas.gerar-ia')->middleware(['auth', 'email-marketing']);
Route::post('/email-campanhas', [EmailCampanhaController::class, 'store'])->name('email-campanhas.store')->middleware(['auth', 'email-marketing']);
Route::get('/email-campanhas/{emailCampanha}', [EmailCampanhaController::class, 'show'])->name('email-campanhas.show')->middleware(['auth', 'email-marketing']);
Route::get('/email-campanhas/{emailCampanha}/editar', [EmailCampanhaController::class, 'edit'])->name('email-campanhas.edit')->middleware(['auth', 'email-marketing']);
Route::put('/email-campanhas/{emailCampanha}', [EmailCampanhaController::class, 'update'])->name('email-campanhas.update')->middleware(['auth', 'email-marketing']);
Route::post('/email-campanhas/{emailCampanha}/enviar', [EmailCampanhaController::class, 'enviar'])->name('email-campanhas.enviar')->middleware(['auth', 'email-marketing']);
Route::delete('/email-campanhas/{emailCampanha}', [EmailCampanhaController::class, 'destroy'])->name('email-campanhas.destroy')->middleware(['auth', 'email-marketing']);

// Classificação — Questionários (admin)
Route::get('/classificacao/questionarios', [QuestionarioController::class, 'index'])->name('questionarios.index')->middleware('auth');
Route::get('/classificacao/questionarios/{id}', [QuestionarioController::class, 'show'])->name('questionarios.show')->middleware('auth');
Route::get('/classificacao/respostas/{id}', [QuestionarioController::class, 'detalheResposta'])->name('questionarios.respostas.detalhe')->middleware('auth');
Route::delete('/classificacao/respostas/{id}', [QuestionarioController::class, 'deleteResposta'])->name('questionarios.respostas.delete')->middleware('auth');
Route::post('/classificacao/questionarios/{id}/enviar-link', [QuestionarioController::class, 'enviarLink'])->name('questionarios.enviar-link')->middleware('auth');
Route::get('/classificacao/vinculos', [QuestionarioController::class, 'vinculos'])->name('questionarios.vinculos')->middleware('auth');
// Questionário público (para leads e clientes responderem)
Route::get('/q/{slug}', [QuestionarioController::class, 'publico'])->name('questionario.publico');
Route::post('/q/{slug}/iniciar', [QuestionarioController::class, 'iniciar'])->name('questionario.iniciar')->middleware('throttle:20,1');
Route::post('/q/{slug}/responder', [QuestionarioController::class, 'responder'])->name('questionario.responder')->middleware('throttle:60,1');

// Ideias & Correções routes
Route::get('/ideias', [IdeiaController::class, 'index'])->name('ideias.index')->middleware('auth');
Route::get('/ideias/form', [IdeiaController::class, 'form'])->name('ideias.form')->middleware('auth');
Route::get('/ideias/{id}/form', [IdeiaController::class, 'formEdit'])->name('ideias.form.edit')->middleware('auth');
Route::post('/ideias/save', [IdeiaController::class, 'store'])->name('ideias.store')->middleware('auth');
Route::patch('/ideias/{id}/status', [IdeiaController::class, 'updateStatus'])->name('ideias.update-status')->middleware('auth');
Route::delete('/ideias/{id}', [IdeiaController::class, 'destroy'])->name('ideias.destroy')->middleware('auth');

// NFS-e Portal Nacional
Route::middleware('auth')->prefix('nfse')->name('nfse.')->group(function () {
    Route::get('/', [NfseController::class, 'index'])->name('index');
    Route::get('/certificado/{clienteId}', [NfseController::class, 'getCertificado'])->name('certificado.get');
    Route::post('/certificado', [NfseController::class, 'salvarCertificado'])->name('certificado.salvar');
    Route::post('/certificado/{clienteId}/reset-nsu', [NfseController::class, 'resetarNsu'])->name('certificado.reset-nsu');
    Route::post('/buscar', [NfseController::class, 'buscar'])->name('buscar');
    Route::get('/xml/download', [NfseController::class, 'downloadXml'])->name('xml.download');
    Route::post('/xml/zip', [NfseController::class, 'downloadZip'])->name('xml.zip');
    Route::post('/xml/zip-xmls', [NfseController::class, 'downloadZipXmls'])->name('xml.zip-xmls');
    Route::get('/danfse', [NfseController::class, 'danfse'])->name('danfse');
    Route::get('/danfse-tecnos', [NfseController::class, 'danfseTecnos'])->name('danfse-tecnos');
    Route::get('/certificado/{clienteId}/download', [NfseController::class, 'downloadCertificado'])->name('certificado.download');
    Route::post('/exportar-excel', [NfseController::class, 'exportarExcel'])->name('exportar-excel');
    Route::post('/exportar-excel-nsus', [NfseController::class, 'exportarExcelNsus'])->name('exportar-excel-nsus');
});

// NF-e / CT-e — Distribuição DFe (Ambiente Nacional)
Route::middleware('auth')->prefix('nfe')->name('nfe.')->group(function () {
    Route::get('/', [NfeController::class, 'index'])->name('index');
    Route::post('/buscar', [NfeController::class, 'buscar'])->name('buscar');
    Route::post('/xml/zip-xmls', [NfeController::class, 'downloadZipXmls'])->name('xml.zip-xmls');
});


// Acesso externo — restrito a Diretor e TI
Route::prefix('admin/acesso-externo')->name('acesso-externo.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AcessoExternoController::class, 'index'])->name('index');

    // Redes permitidas
    Route::post('/redes', [AcessoExternoController::class, 'storeRede'])->name('redes.store');
    Route::patch('/redes/{rede}/toggle', [AcessoExternoController::class, 'toggleRede'])->name('redes.toggle');
    Route::delete('/redes/{rede}', [AcessoExternoController::class, 'destroyRede'])->name('redes.destroy');

    // Liberações por usuário
    Route::post('/liberacoes', [AcessoExternoController::class, 'storeLiberacao'])->name('liberacoes.store');
    Route::delete('/liberacoes/{liberacao}', [AcessoExternoController::class, 'revogarLiberacao'])->name('liberacoes.revogar');
});
