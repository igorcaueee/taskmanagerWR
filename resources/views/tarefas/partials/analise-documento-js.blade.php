// ── Análise do documento com IA (usado nos modais de envio ao portal) ────────
// Ao escolher o arquivo, a IA lê a guia/documento, pré-preenche os campos vazios e
// confere o CNPJ/CPF do documento com o cliente. Se divergir, o envio exige confirmação.
let analiseDocumento = null;
let analiseDocumentoConfig = {};

/**
 * Chamar ao abrir o modal.
 * clienteId: cliente fixo (tarefa) — a conferência vem do servidor.
 * obterCliente: () => ({ id, doc }) | null — cliente escolhido no select (upload avulso), conferido aqui no navegador.
 * selecionarCliente: (id) => void — permite o botão "Trocar para ..." quando o documento é de outro cliente.
 */
function configurarAnaliseDocumento(config = {}) {
    analiseDocumento = null;
    analiseDocumentoConfig = config;
}

function iniciarAnaliseDocumento(arquivo) {
    const estado = { resultado: null, erro: null, carregando: true };
    analiseDocumento = estado;
    renderAnaliseDocumento();

    const formData = new FormData();
    formData.append('arquivo', arquivo);
    if (analiseDocumentoConfig.clienteId) formData.append('cliente_id', analiseDocumentoConfig.clienteId);

    estado.promise = fetch('{{ route('tarefas.uploads-portal.analisar') }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData,
    })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) { throw new Error(data.error ?? (res.status === 413 ? 'Arquivo grande demais para análise.' : 'Não foi possível analisar o arquivo.')); }
            return data;
        })
        .then(data => {
            if (analiseDocumento !== estado) { return; } // outro arquivo foi escolhido no meio
            estado.resultado = data;
            if (data.analisado) { aplicarAnaliseDocumento(data); }
        })
        .catch(e => { if (analiseDocumento === estado) { estado.erro = e.message; } })
        .finally(() => {
            if (analiseDocumento !== estado) { return; }
            estado.carregando = false;
            renderAnaliseDocumento();
        });
}

/** Preenche só o que ainda está vazio (ou o período padrão), sem sobrescrever o que o usuário digitou. */
function aplicarAnaliseDocumento(d) {
    const preencher = (id, valor) => {
        const el = document.getElementById(id);
        if (el && !el.value && valor !== null && valor !== undefined) { el.value = valor; }
    };

    const selectCliente = document.getElementById('swal-cliente');
    if (selectCliente && !selectCliente.value && d.cliente?.temPasta) {
        selectCliente.value = d.cliente.id;
        selectCliente.dispatchEvent(new Event('change'));
    }

    preencher('swal-tipo-arquivo', d.tipo_arquivo);
    onTipoArquivoChange();
    preencher('swal-data-vencimento', d.data_vencimento);
    preencher('swal-valor', d.valor);
    preencher('swal-pasta-categoria', d.pasta_categoria);

    const periodo = document.getElementById('swal-pasta-periodo');
    if (periodo && d.pasta_periodo && (!periodo.value.trim() || periodo.value.trim() === gerarPeriodoPadrao())) {
        periodo.value = d.pasta_periodo;
    }
}

function conferenciaAnaliseDocumento(d) {
    if (!analiseDocumentoConfig.obterCliente) { return d.conferencia ?? null; }

    const cliente = analiseDocumentoConfig.obterCliente();
    const docDocumento = d.cnpj_cpf ?? '';
    const docCliente = cliente?.doc ?? '';
    if (!cliente || !docDocumento || !docCliente) { return null; }
    if (docDocumento === docCliente) { return 'confere'; }
    if (docDocumento.length === 14 && docCliente.length === 14 && docDocumento.slice(0, 8) === docCliente.slice(0, 8)) { return 'filial'; }
    return 'diverge';
}

function escHtmlAnalise(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function formatarCnpjCpf(doc) {
    if (!doc) { return ''; }
    if (doc.length === 14) { return doc.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5'); }
    if (doc.length === 11) { return doc.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4'); }
    return doc;
}

function renderAnaliseDocumento() {
    const box = document.getElementById('swal-analise-ia');
    if (!box) { return; }

    const estado = analiseDocumento;
    if (!estado) { box.classList.add('hidden'); box.innerHTML = ''; return; }
    box.classList.remove('hidden');

    const caixa = (cor, conteudo) => `<div class="rounded-lg border px-3 py-2 text-xs ${cor}">${conteudo}</div>`;
    const cinza = 'bg-gray-50 border-gray-200 text-gray-500';

    if (estado.carregando) {
        box.innerHTML = caixa('bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300', '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Analisando o documento com IA...');
        return;
    }
    if (estado.erro) {
        box.innerHTML = caixa(cinza, `<i class="fa-solid fa-circle-info mr-1"></i> ${escHtmlAnalise(estado.erro)}`);
        return;
    }

    const d = estado.resultado;
    if (!d.analisado) {
        box.innerHTML = caixa(cinza, `<i class="fa-solid fa-circle-info mr-1"></i> ${escHtmlAnalise(d.motivo ?? 'Documento não analisado.')} Preencha os campos manualmente.`);
        return;
    }

    const codigos = (d.codigos_receita ?? []).map(c =>
        `<span class="inline-block bg-white dark:bg-slate-800 border border-gray-200 rounded px-1.5 py-0.5 mr-1 mt-1 text-gray-600" title="${escHtmlAnalise(c.descricao ?? 'Código não cadastrado na tabela')}">${escHtmlAnalise(c.codigo)}${c.descricao ? ' · ' + escHtmlAnalise(c.descricao) : ''}</span>`
    ).join('');

    const titular = d.cnpj_cpf
        ? `<i class="fa-regular fa-id-card mr-1"></i>${formatarCnpjCpf(d.cnpj_cpf)}${d.nome_titular ? ' · ' + escHtmlAnalise(d.nome_titular) : ''}`
        : '<i class="fa-regular fa-id-card mr-1"></i>CNPJ/CPF não encontrado no documento';

    const resumo = `
        <p class="font-semibold text-gray-700 dark:text-slate-200"><i class="fa-solid fa-wand-magic-sparkles mr-1 text-brand"></i>${escHtmlAnalise(d.descricao ?? d.tipo_documento ?? 'Documento analisado')}</p>
        <p class="mt-1 text-gray-600 dark:text-slate-300">${titular}</p>
        ${codigos ? `<div>${codigos}</div>` : ''}`;

    const conferencia = conferenciaAnaliseDocumento(d);
    let status = '';

    if (conferencia === 'confere') {
        status = '<p class="mt-2 text-green-700 dark:text-green-300 font-semibold"><i class="fa-solid fa-circle-check mr-1"></i>CNPJ/CPF confere com o cliente.</p>';
    } else if (conferencia === 'filial') {
        status = '<p class="mt-2 text-amber-700 dark:text-amber-300 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Documento de outro estabelecimento (mesma raiz de CNPJ) do cliente.</p>';
    } else if (conferencia === 'diverge') {
        const outro = d.cliente
            ? ` O documento é de <strong>${escHtmlAnalise(d.cliente.nome)}</strong>.${analiseDocumentoConfig.selecionarCliente && d.cliente.temPasta
                ? ` <button type="button" onclick="trocarClienteAnaliseDocumento()" class="bg-transparent border-0 p-0 appearance-none text-red-700 dark:text-red-300 underline font-semibold cursor-pointer">Trocar para este cliente</button>`
                : ''}`
            : ' Nenhum cliente cadastrado tem esse CNPJ/CPF.';
        status = `
            <p class="mt-2 text-red-700 dark:text-red-300 font-semibold"><i class="fa-solid fa-circle-xmark mr-1"></i>CNPJ/CPF do documento não confere com o cliente selecionado.</p>
            <p class="mt-1 text-red-700 dark:text-red-300">${outro}</p>
            <label class="flex items-center gap-2 mt-2 text-red-700 dark:text-red-300 cursor-pointer select-none">
                <input type="checkbox" id="swal-analise-ignorar" class="w-4 h-4 rounded accent-red-600"> Enviar mesmo assim
            </label>`;
    } else if (d.cnpj_cpf && !d.cliente) {
        status = '<p class="mt-2 text-amber-700 dark:text-amber-300 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Nenhum cliente cadastrado tem esse CNPJ/CPF.</p>';
    } else if (d.cliente && !d.cliente.temPasta) {
        status = `<p class="mt-2 text-amber-700 dark:text-amber-300 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Documento de ${escHtmlAnalise(d.cliente.nome)}, mas esse cliente não tem pasta do portal configurada. Configure a pasta no cadastro do cliente para poder enviar.</p>`;
    } else if (d.cliente && analiseDocumentoConfig.selecionarCliente) {
        status = `<p class="mt-2 text-gray-600 dark:text-slate-300"><i class="fa-solid fa-building mr-1"></i>Cliente identificado: <strong>${escHtmlAnalise(d.cliente.nome)}</strong>${d.cliente.filial ? ' (outro estabelecimento, mesma raiz de CNPJ)' : ''}</p>`;
    }

    const cor = conferencia === 'diverge'
        ? 'bg-red-50 border-red-200 dark:bg-red-900/30 dark:border-red-800'
        : (conferencia === 'confere' ? 'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-800' : 'bg-gray-50 border-gray-200');
    box.innerHTML = caixa(cor, resumo + status);
}

function trocarClienteAnaliseDocumento() {
    const cliente = analiseDocumento?.resultado?.cliente;
    if (!cliente || !analiseDocumentoConfig.selecionarCliente) { return; }
    analiseDocumentoConfig.selecionarCliente(cliente.id);
    renderAnaliseDocumento();
}

/** Para o preConfirm: espera a análise terminar e devolve a mensagem de bloqueio, ou null se pode enviar. */
async function validarAnaliseDocumento() {
    if (!analiseDocumento) { return null; }
    await analiseDocumento.promise;

    const d = analiseDocumento.resultado;
    if (!d?.analisado || conferenciaAnaliseDocumento(d) !== 'diverge') { return null; }

    return document.getElementById('swal-analise-ignorar')?.checked
        ? null
        : 'O CNPJ/CPF do documento não confere com o cliente. Corrija ou marque "Enviar mesmo assim".';
}

/**
 * Bloco dos modais de envio: deixa claro que o aviso por e-mail sai sempre e oferece mandar o link de download junto.
 * Vem marcado quando o cliente está configurado para receber arquivos por e-mail.
 */
function htmlOpcoesEmailPortal(marcado = false) {
    return `
        <div class="flex items-start gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-300 text-left">
            <i class="fa-solid fa-envelope-circle-check mt-0.5"></i>
            <span>O cliente será avisado por e-mail (contatos e usuários do portal) de que há um arquivo novo no portal.</span>
        </div>
        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer select-none mt-3 text-left">
            <input type="checkbox" id="swal-enviar-link-email" ${marcado ? 'checked' : ''} class="w-4 h-4 mt-0.5 rounded accent-indigo-600">
            <span>
                <i class="fa-solid fa-envelope text-indigo-500"></i> Enviar também por e-mail
                <span class="block text-xs text-gray-400">O e-mail de aviso vai com o link para baixar o arquivo.</span>
            </span>
        </label>`;
}

/** Texto para o usuário sobre o aviso por e-mail devolvido pelo upload. */
function mensagemAvisoEmail(data) {
    if (data.aviso_email === 'enviado') {
        return `${data.email_com_link ? 'E-mail com link de download' : 'Aviso'} enviado para ${data.emails_notificados.join(', ')}.`;
    }
    if (data.aviso_email === 'falhou') { return 'Não foi possível enviar o aviso por e-mail. Avise o cliente manualmente.'; }
    return 'Nenhum e-mail cadastrado nos contatos ou usuários do portal deste cliente: o aviso não foi enviado.';
}

function descricaoAnaliseDocumento() {
    const d = analiseDocumento?.resultado;
    return d?.analisado ? (d.descricao ?? '') : '';
}
