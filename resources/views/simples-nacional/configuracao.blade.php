@extends('layouts.internal')

@section('title', 'Configuração da API — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-key"></i> Configuração da API</h1>
            <p class="text-gray-700 dark:text-gray-300">Certificado do escritório e chaves de acesso à API Integra Contador (SERPRO).</p>
        </div>

        {{-- ─── Configuração da API Integra Contador (SERPRO) ──────────────────── --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-key text-brand"></i> Configuração da API (certificado do escritório)
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                        Procuração eletrônica única do escritório — certificado e-CNPJ (.pfx/.p12), CNPJ contratante e as chaves de acesso (Consumer Key/Secret) obtidas na Área do Cliente SERPRO.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" id="btnAbrirConfigSerpro" class="underline text-brand bg-transparent border-0 text-sm whitespace-nowrap">Editar configuração</button>
                </div>
            </div>

            <div id="configSerproStatus" class="mt-3 space-y-2">
                <div id="configSerproOk" class="hidden items-center gap-2 text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Configurado — CNPJ contratante <strong id="configSerproCnpj"></strong>, ambiente <strong id="configSerproAmbiente"></strong></span>
                </div>
                <div id="configSerproNone" class="hidden items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-700 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Nenhuma configuração cadastrada ainda — cadastre o certificado e as chaves para habilitar o processamento.</span>
                </div>
            </div>

            {{-- Ferramentas técnicas de teste direto da API — uso avançado/depuração --}}
            <details id="detalheFerramentasTeste" class="hidden mt-3">
                <summary class="text-xs text-gray-500 dark:text-slate-400 cursor-pointer select-none">Ferramentas avançadas de teste da API</summary>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <button type="button" id="btnTestarConexaoSerpro" class="underline text-brand bg-transparent border-0 text-sm whitespace-nowrap">Testar conexão</button>
                    <input type="text" id="inputCnpjTesteConsulta" placeholder="CNPJ para teste"
                           class="border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-xs text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
                    <button type="button" id="btnTestarConsultaDemo" class="underline text-brand bg-transparent border-0 text-sm whitespace-nowrap">Testar consulta PGDASD</button>
                    <input type="text" id="inputNumeroDeclaracaoTeste" placeholder="Nº declaração (para testar recibo)"
                           class="border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-xs text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
                    <button type="button" id="btnTestarConsultaRecibo" class="underline text-brand bg-transparent border-0 text-sm whitespace-nowrap">Testar recibo (RBT12?)</button>
                </div>
                <div id="configSerproTesteResultado" class="hidden items-center gap-2 text-sm rounded-lg px-3 py-2 mt-2"></div>
                <div id="configSerproConsultaResultado" class="hidden text-sm rounded-lg px-3 py-2 mt-2">
                    <p id="configSerproConsultaMensagem" class="mb-1"></p>
                    <pre id="configSerproConsultaJson" class="text-xs overflow-x-auto whitespace-pre-wrap"></pre>
                </div>
            </details>

            {{-- Form de upload/edição --}}
            <div id="formConfigSerpro" class="hidden mt-4 pt-4 border-t border-gray-100 dark:border-slate-700 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Certificado do escritório (.pfx/.p12)</label>
                    <input type="file" id="inputCertSerpro" accept=".pfx,.p12"
                           class="w-full text-sm text-gray-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand file:text-white file:text-xs">
                    <p class="text-xs text-gray-400 mt-1">Deixe em branco para manter o certificado já cadastrado (só altera se enviar um novo arquivo).</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Senha do certificado</label>
                    <input type="password" id="inputSenhaSerpro"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CNPJ contratante</label>
                    <input type="text" id="inputCnpjSerpro" placeholder="00.000.000/0000-00"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Consumer Key</label>
                    <input type="text" id="inputConsumerKey"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Consumer Secret</label>
                    <input type="password" id="inputConsumerSecret"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ambiente</label>
                    <select id="selectAmbienteSerpro"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="trial">Trial</option>
                        <option value="producao">Produção</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="button" id="btnSalvarConfigSerpro"
                            class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        Salvar configuração
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const btnAbrirConfigSerpro = document.getElementById('btnAbrirConfigSerpro');
    const formConfigSerpro     = document.getElementById('formConfigSerpro');
    const configSerproOk       = document.getElementById('configSerproOk');
    const configSerproNone     = document.getElementById('configSerproNone');
    const inputCertSerpro      = document.getElementById('inputCertSerpro');
    const inputSenhaSerpro     = document.getElementById('inputSenhaSerpro');
    const inputCnpjSerpro      = document.getElementById('inputCnpjSerpro');
    const inputConsumerKey     = document.getElementById('inputConsumerKey');
    const inputConsumerSecret  = document.getElementById('inputConsumerSecret');
    const selectAmbienteSerpro = document.getElementById('selectAmbienteSerpro');
    const btnSalvarConfigSerpro = document.getElementById('btnSalvarConfigSerpro');
    const btnTestarConexaoSerpro = document.getElementById('btnTestarConexaoSerpro');
    const btnTestarConsultaDemo = document.getElementById('btnTestarConsultaDemo');
    const inputCnpjTesteConsulta = document.getElementById('inputCnpjTesteConsulta');
    const btnTestarConsultaRecibo = document.getElementById('btnTestarConsultaRecibo');
    const inputNumeroDeclaracaoTeste = document.getElementById('inputNumeroDeclaracaoTeste');
    const configSerproTesteResultado = document.getElementById('configSerproTesteResultado');
    const configSerproConsultaResultado = document.getElementById('configSerproConsultaResultado');
    const configSerproConsultaMensagem = document.getElementById('configSerproConsultaMensagem');
    const configSerproConsultaJson = document.getElementById('configSerproConsultaJson');
    const detalheFerramentasTeste = document.getElementById('detalheFerramentasTeste');

    async function carregarStatusConfigSerpro() {
        const resp = await fetch('{{ route('simples-nacional.configuracao.get') }}', { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        [configSerproOk, configSerproNone].forEach(el => el.classList.add('hidden'));
        configSerproTesteResultado.classList.add('hidden');
        configSerproConsultaResultado.classList.add('hidden');
        detalheFerramentasTeste.classList.add('hidden');

        if (!data.configurado || !data.arquivo_ok) {
            configSerproNone.classList.remove('hidden');
            formConfigSerpro.classList.remove('hidden');
            return;
        }

        configSerproOk.classList.remove('hidden');
        detalheFerramentasTeste.classList.remove('hidden');
        document.getElementById('configSerproCnpj').textContent = data.cnpj_contratante;
        document.getElementById('configSerproAmbiente').textContent = data.ambiente === 'producao' ? 'Produção' : 'Trial';
        inputCnpjSerpro.value = data.cnpj_contratante;
        selectAmbienteSerpro.value = data.ambiente;
    }

    btnAbrirConfigSerpro?.addEventListener('click', function () {
        formConfigSerpro.classList.toggle('hidden');
    });

    btnTestarConexaoSerpro.addEventListener('click', async function () {
        this.disabled = true;
        this.textContent = 'Testando...';
        configSerproTesteResultado.classList.add('hidden');

        try {
            const resp = await fetch('{{ route('simples-nacional.configuracao.testar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await resp.json();

            configSerproTesteResultado.classList.remove('hidden');
            configSerproTesteResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                configSerproTesteResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                configSerproTesteResultado.textContent = data.error ?? 'Falha ao testar a conexão.';
            } else {
                configSerproTesteResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
                configSerproTesteResultado.textContent = data.message;
            }
        } catch (e) {
            configSerproTesteResultado.classList.remove('hidden');
            configSerproTesteResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            configSerproTesteResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
            this.textContent = 'Testar conexão';
        }
    });

    btnTestarConsultaDemo.addEventListener('click', async function () {
        this.disabled = true;
        this.textContent = 'Consultando...';
        configSerproConsultaResultado.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.configuracao.testar-consulta-demo') }}');
            if (inputCnpjTesteConsulta.value) {
                url.searchParams.set('cnpj', inputCnpjTesteConsulta.value);
            }

            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await resp.json();

            configSerproConsultaResultado.classList.remove('hidden');
            configSerproConsultaResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                configSerproConsultaResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                configSerproConsultaMensagem.textContent = data.error ?? 'Falha na consulta PGDASD.';
                configSerproConsultaJson.textContent = '';
            } else {
                configSerproConsultaResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
                configSerproConsultaMensagem.textContent = data.message;
                configSerproConsultaJson.textContent = JSON.stringify(data.resposta, null, 2);
            }
        } catch (e) {
            configSerproConsultaResultado.classList.remove('hidden');
            configSerproConsultaResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            configSerproConsultaMensagem.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
            this.textContent = 'Testar consulta PGDASD';
        }
    });

    btnTestarConsultaRecibo.addEventListener('click', async function () {
        if (!inputCnpjTesteConsulta.value || !inputNumeroDeclaracaoTeste.value) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha o CNPJ e o número da declaração (veja no resultado da consulta anterior).' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Consultando...';
        configSerproConsultaResultado.classList.add('hidden');

        try {
            const resp = await fetch('{{ route('simples-nacional.configuracao.testar-consulta-recibo') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cnpj: inputCnpjTesteConsulta.value,
                    numero_declaracao: inputNumeroDeclaracaoTeste.value,
                }),
            });
            const data = await resp.json();

            configSerproConsultaResultado.classList.remove('hidden');
            configSerproConsultaResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                configSerproConsultaResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                configSerproConsultaMensagem.textContent = data.error ?? 'Falha na consulta de recibo.';
                configSerproConsultaJson.textContent = '';
            } else {
                configSerproConsultaResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');

                const links = (data.arquivos ?? []).map(a => `<a href="${escapeHtml(a.url)}" target="_blank" class="underline">${escapeHtml(a.nomeArquivo)}</a>`).join(' | ');
                configSerproConsultaMensagem.innerHTML = escapeHtml(data.message) + (links ? '<br>PDFs: ' + links : '');
                configSerproConsultaJson.textContent = JSON.stringify(data.dados_sem_pdf, null, 2);
            }
        } catch (e) {
            configSerproConsultaResultado.classList.remove('hidden');
            configSerproConsultaResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            configSerproConsultaMensagem.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
            this.textContent = 'Testar recibo (RBT12?)';
        }
    });

    btnSalvarConfigSerpro.addEventListener('click', async function () {
        if (!inputCnpjSerpro.value || !inputConsumerKey.value || !inputConsumerSecret.value) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha CNPJ contratante, Consumer Key e Consumer Secret.' });
            return;
        }

        const formData = new FormData();
        if (inputCertSerpro.files[0]) {
            formData.append('certificado', inputCertSerpro.files[0]);
            formData.append('senha', inputSenhaSerpro.value);
        }
        formData.append('cnpj_contratante', inputCnpjSerpro.value);
        formData.append('consumer_key', inputConsumerKey.value);
        formData.append('consumer_secret', inputConsumerSecret.value);
        formData.append('ambiente', selectAmbienteSerpro.value);

        this.disabled = true;
        this.textContent = 'Salvando...';

        try {
            const resp = await fetch('{{ route('simples-nacional.configuracao.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
                body: formData,
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar configuração.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message });
            inputSenhaSerpro.value = '';
            inputConsumerSecret.value = '';
            inputCertSerpro.value = '';
            formConfigSerpro.classList.add('hidden');
            await carregarStatusConfigSerpro();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            this.textContent = 'Salvar configuração';
        }
    });

    carregarStatusConfigSerpro();
    </script>
    @endpush
@endsection
