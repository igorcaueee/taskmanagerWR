@extends('layouts.internal')

@section('title', 'Emitir NFS-e — ' . $cliente->nome)

@section('content')
<div class="w-full mx-auto py-6 px-4 max-w-4xl">

    <div class="mb-6">
        <a href="{{ route('nfse.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2 mt-1">
            <i class="fa-solid fa-file-circle-plus text-[#0084aa]"></i>
            Emitir NFS-e — {{ $cliente->nome }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Emissão via Sistema Nacional NFS-e (ADN). Ambiente atual do certificado: <strong>{{ $certificado->ambiente ?? 'não configurado' }}</strong>.</p>
    </div>

    @include('nfse._tabs')

    {{-- Trocar de empresa sem voltar pra aba Consultar --}}
    <div class="mb-6">
        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Empresa (prestador)</label>
        <select id="selectEmpresaEmitir"
                class="w-full max-w-md rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            @foreach($clientes as $cli)
                <option value="{{ $cli->id }}" @selected($cli->id === $cliente->id)>{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
            @endforeach
        </select>
    </div>

    @if(!$certificado)
        <div class="mb-6 flex items-center gap-2 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-4 py-3">
            <i class="fa-solid fa-circle-xmark"></i>
            <span>Este cliente não possui certificado digital cadastrado. <a href="{{ route('nfse.index') }}" class="underline">Configure o certificado</a> antes de emitir.</span>
        </div>
    @endif

    {{-- Dados fiscais do prestador --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide">
                <i class="fa-regular fa-building mr-1 text-[#0084aa]"></i> Dados Fiscais do Prestador
            </h2>
            <div class="flex items-center gap-3">
                @if($dadosFiscais && $dadosFiscais->completo())
                    <span class="text-xs text-green-700 dark:text-green-400"><i class="fa-solid fa-circle-check mr-1"></i>Completo</span>
                @else
                    <span class="text-xs text-amber-700 dark:text-amber-400"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Incompleto</span>
                @endif
                @if($cliente->tipo !== 'PF')
                    <button type="button" id="btnBuscarCnpjPrestador"
                            class="text-xs text-[#0084aa] hover:underline bg-transparent border-0 p-0 flex items-center gap-1">
                        <i id="prestadorCnpjLoading" class="hidden fa-solid fa-spinner fa-spin"></i>
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar endereço via CNPJ
                    </button>
                @endif
            </div>
        </div>

        <form id="formDadosFiscais" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Inscrição Municipal (opcional)</label>
                <input type="text" id="inscricao_municipal" value="{{ $dadosFiscais->inscricao_municipal ?? '' }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Código IBGE do Município *</label>
                <input type="text" id="codigo_municipio_ibge" maxlength="7" value="{{ $dadosFiscais->codigo_municipio_ibge ?? '' }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CEP *</label>
                <input type="text" id="cep" value="{{ $dadosFiscais->cep ?? '' }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Logradouro *</label>
                <input type="text" id="logradouro" value="{{ $dadosFiscais->logradouro ?? '' }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Número *</label>
                <input type="text" id="numero_fiscal" value="{{ $dadosFiscais->numero ?? '' }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Complemento</label>
                <input type="text" id="complemento_fiscal" value="{{ $dadosFiscais->complemento ?? '' }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Bairro *</label>
                <input type="text" id="bairro_fiscal" value="{{ $dadosFiscais->bairro ?? '' }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            </div>
            <div class="md:col-span-3 flex justify-end">
                <button type="button" id="btnSalvarDadosFiscais"
                        class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar dados fiscais
                </button>
            </div>
        </form>
    </div>

    {{-- Formulário de emissão --}}
    <form id="formEmissao" class="space-y-6">

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">
                <i class="fa-solid fa-user mr-1 text-[#0084aa]"></i> Tomador do Serviço
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Tipo de Documento</label>
                    <select id="tomador_tipo_doc" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                        <option value="CNPJ">CNPJ</option>
                        <option value="CPF">CPF</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">
                        CPF/CNPJ *
                        <i id="tomadorCnpjLoading" class="hidden fa-solid fa-spinner fa-spin text-[#0084aa] ml-1"></i>
                    </label>
                    <input type="text" id="tomador_cpf_cnpj" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">E-mail</label>
                    <input type="email" id="tomador_email" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Nome / Razão Social *</label>
                    <input type="text" id="tomador_nome" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Código IBGE do Município</label>
                    <input type="text" id="tomador_codigo_municipio_ibge" maxlength="7" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CEP</label>
                    <input type="text" id="tomador_cep" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Número</label>
                    <input type="text" id="tomador_numero" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Logradouro</label>
                    <input type="text" id="tomador_logradouro" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Bairro</label>
                    <input type="text" id="tomador_bairro" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">
                <i class="fa-solid fa-briefcase mr-1 text-[#0084aa]"></i> Serviço
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Código de Tributação Nacional (LC 116) *</label>
                    <select id="codigo_tributacao_nacional" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                        <option value="">Selecione...</option>
                        @foreach($servicosNacionais as $servico)
                            <option value="{{ $servico->codigo_tributacao_nacional }}">{{ $servico->codigo_tributacao_nacional }} — {{ Str::limit($servico->descricao, 80) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Descrição do Serviço Prestado *</label>
                    <textarea id="descricao_servico" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Valor do Serviço (R$) *</label>
                    <input type="number" step="0.01" min="0.01" id="valor_servico" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Alíquota ISS (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="aliquota" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Data de Competência *</label>
                    <input type="date" id="dcompet" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Tributação do ISSQN</label>
                    <select id="trib_issqn" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                        <option value="1">Operação tributável</option>
                        <option value="2">Imunidade</option>
                        <option value="3">Exportação de serviço</option>
                        <option value="4">Não incidência</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Desconto Incondicional (R$)</label>
                    <input type="number" step="0.01" min="0" id="desconto_incondicional" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Código IBGE do Local da Prestação</label>
                    <input type="text" maxlength="7" id="codigo_municipio_prestacao" value="{{ $dadosFiscais->codigo_municipio_ibge ?? '' }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                    <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">Preenchido com o município do prestador — altere só se o serviço foi prestado em outra cidade.</p>
                </div>
                <div class="flex items-center gap-2 mt-5">
                    <input type="checkbox" id="iss_retido" class="rounded border-gray-300 dark:border-slate-600 text-[#0084aa] focus:ring-[#0084aa]">
                    <label for="iss_retido" class="text-sm text-gray-700 dark:text-slate-300">ISS retido pelo tomador</label>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="button" id="btnEmitir" {{ !$certificado ? 'disabled' : '' }}
                    class="py-2.5 px-6 bg-[#0084aa] hover:bg-[#006e8e] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Emitir NFS-e
            </button>
        </div>
    </form>

    <div class="mt-6 text-right">
        <a href="{{ route('nfse.emissoes', $cliente) }}" class="text-sm text-[#0084aa] hover:underline">Ver notas emitidas para este cliente →</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const clienteId = {{ $cliente->id }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    document.getElementById('selectEmpresaEmitir').addEventListener('change', function () {
        localStorage.setItem('nfseClienteAtual', this.value);
        window.location.href = `/nfse/emitir/${this.value}`;
    });

    // ─── Autopreenchimento do tomador via consulta pública de CNPJ ──────────
    const tomadorTipoDoc = document.getElementById('tomador_tipo_doc');
    const tomadorCpfCnpj = document.getElementById('tomador_cpf_cnpj');
    const tomadorCnpjLoading = document.getElementById('tomadorCnpjLoading');
    let ultimoCnpjConsultado = null;

    async function buscarDadosCnpjTomador() {
        if (tomadorTipoDoc.value !== 'CNPJ') return;

        const cnpj = tomadorCpfCnpj.value.replace(/\D/g, '');
        if (cnpj.length !== 14 || cnpj === ultimoCnpjConsultado) return;
        ultimoCnpjConsultado = cnpj;

        tomadorCnpjLoading.classList.remove('hidden');
        try {
            const resp = await fetch(`/nfse/consultar-cnpj/${cnpj}`, { headers: { 'Accept': 'application/json' } });
            if (!resp.ok) return; // CNPJ não encontrado — deixa o usuário preencher manualmente

            const dados = await resp.json();

            if (dados.razao_social) document.getElementById('tomador_nome').value = dados.razao_social;
            if (dados.email && !document.getElementById('tomador_email').value) document.getElementById('tomador_email').value = dados.email;
            if (dados.cep) document.getElementById('tomador_cep').value = dados.cep;
            if (dados.logradouro) document.getElementById('tomador_logradouro').value = dados.logradouro;
            if (dados.numero) document.getElementById('tomador_numero').value = dados.numero;
            if (dados.bairro) document.getElementById('tomador_bairro').value = dados.bairro;
            if (dados.codigo_municipio_ibge) document.getElementById('tomador_codigo_municipio_ibge').value = dados.codigo_municipio_ibge;
        } catch (e) {
            // Falha silenciosa — consulta pública é só uma conveniência, não bloqueia a emissão
        } finally {
            tomadorCnpjLoading.classList.add('hidden');
        }
    }

    tomadorCpfCnpj.addEventListener('blur', buscarDadosCnpjTomador);
    tomadorTipoDoc.addEventListener('change', () => { ultimoCnpjConsultado = null; });

    // Alíquota/retenção só fazem sentido em operação tributável
    const tribIssqn = document.getElementById('trib_issqn');
    const aliquotaInput = document.getElementById('aliquota');
    const issRetidoInput = document.getElementById('iss_retido');
    tribIssqn.addEventListener('change', function () {
        const tributavel = this.value === '1';
        aliquotaInput.disabled = !tributavel;
        issRetidoInput.disabled = !tributavel;
        if (!tributavel) {
            aliquotaInput.value = '';
            issRetidoInput.checked = false;
        }
    });

    // ─── Autopreenchimento do endereço fiscal do prestador via CNPJ do cliente ──
    document.getElementById('btnBuscarCnpjPrestador')?.addEventListener('click', async function () {
        const cnpj = '{{ preg_replace('/\D/', '', $cliente->cpfcnpj ?? '') }}';
        if (cnpj.length !== 14) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Este cliente não possui um CNPJ válido cadastrado.' });
            return;
        }

        const loading = document.getElementById('prestadorCnpjLoading');
        loading.classList.remove('hidden');
        try {
            const resp = await fetch(`/nfse/consultar-cnpj/${cnpj}`, { headers: { 'Accept': 'application/json' } });
            if (!resp.ok) {
                Swal.fire({ icon: 'error', title: 'Não encontrado', text: 'Não foi possível localizar este CNPJ na base pública da Receita.' });
                return;
            }

            const dados = await resp.json();

            if (dados.cep) document.getElementById('cep').value = dados.cep;
            if (dados.logradouro) document.getElementById('logradouro').value = dados.logradouro;
            if (dados.numero) document.getElementById('numero_fiscal').value = dados.numero;
            if (dados.complemento) document.getElementById('complemento_fiscal').value = dados.complemento;
            if (dados.bairro) document.getElementById('bairro_fiscal').value = dados.bairro;
            if (dados.codigo_municipio_ibge) document.getElementById('codigo_municipio_ibge').value = dados.codigo_municipio_ibge;

            Swal.fire({ icon: 'success', title: 'Dados encontrados!', text: 'Confira e salve os dados fiscais abaixo. A inscrição municipal precisa ser preenchida manualmente.', timer: 2500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na comunicação com o servidor.' });
        } finally {
            loading.classList.add('hidden');
        }
    });

    document.getElementById('btnSalvarDadosFiscais').addEventListener('click', async function () {
        const payload = {
            inscricao_municipal: document.getElementById('inscricao_municipal').value,
            codigo_municipio_ibge: document.getElementById('codigo_municipio_ibge').value,
            cep: document.getElementById('cep').value,
            logradouro: document.getElementById('logradouro').value,
            numero: document.getElementById('numero_fiscal').value,
            complemento: document.getElementById('complemento_fiscal').value,
            bairro: document.getElementById('bairro_fiscal').value,
        };

        try {
            const resp = await fetch(`/nfse/emitir/${clienteId}/dados-fiscais`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const data = await resp.json();
            if (!resp.ok) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar dados fiscais.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Salvo!', text: 'Dados fiscais atualizados.', timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na comunicação com o servidor.' });
        }
    });

    document.getElementById('btnEmitir').addEventListener('click', async function () {
        const payload = {
            tomador_tipo_doc: document.getElementById('tomador_tipo_doc').value,
            tomador_cpf_cnpj: document.getElementById('tomador_cpf_cnpj').value,
            tomador_nome: document.getElementById('tomador_nome').value,
            tomador_email: document.getElementById('tomador_email').value || null,
            tomador_cep: document.getElementById('tomador_cep').value || null,
            tomador_logradouro: document.getElementById('tomador_logradouro').value || null,
            tomador_numero: document.getElementById('tomador_numero').value || null,
            tomador_bairro: document.getElementById('tomador_bairro').value || null,
            tomador_codigo_municipio_ibge: document.getElementById('tomador_codigo_municipio_ibge').value || null,
            codigo_tributacao_nacional: document.getElementById('codigo_tributacao_nacional').value,
            descricao_servico: document.getElementById('descricao_servico').value,
            codigo_municipio_prestacao: document.getElementById('codigo_municipio_prestacao').value || null,
            valor_servico: document.getElementById('valor_servico').value,
            aliquota: document.getElementById('aliquota').value || null,
            iss_retido: document.getElementById('iss_retido').checked,
            trib_issqn: document.getElementById('trib_issqn').value,
            desconto_incondicional: document.getElementById('desconto_incondicional').value || null,
            dcompet: document.getElementById('dcompet').value,
        };

        if (!payload.tomador_cpf_cnpj || !payload.tomador_nome || !payload.codigo_tributacao_nacional || !payload.descricao_servico || !payload.valor_servico || !payload.dcompet) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha os campos obrigatórios (*).' });
            return;
        }

        const confirm = await Swal.fire({
            icon: 'question',
            title: 'Emitir NFS-e?',
            text: `Valor: R$ ${parseFloat(payload.valor_servico).toFixed(2)} — esta ação envia a DPS ao Sistema Nacional NFS-e.`,
            showCancelButton: true,
            confirmButtonText: 'Emitir',
            cancelButtonText: 'Cancelar',
        });
        if (!confirm.isConfirmed) return;

        Swal.fire({ title: 'Emitindo...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const resp = await fetch(`/nfse/emitir/${clienteId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const data = await resp.json();

            if (!resp.ok) {
                Swal.fire({ icon: 'error', title: 'NFS-e rejeitada', text: data.error ?? 'Falha ao emitir NFS-e.' });
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'NFS-e emitida!',
                html: `Chave de acesso: <strong>${data.chave_acesso}</strong>`,
            }).then(() => {
                window.location.href = `/nfse/emissoes/${clienteId}`;
            });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na comunicação com o servidor.' });
        }
    });
});
</script>
@endsection
