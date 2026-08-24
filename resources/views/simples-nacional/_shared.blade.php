{{--
    Helpers de JS compartilhados por todas as telas do Simples Nacional
    (exceto o hub) — @include'd dentro do @push('scripts') de cada tela.
    Script clássico (sem type="module") de propósito — assim as consts/functions
    ficam no escopo global e são visíveis para o <script> específico de cada
    tela, que vem logo depois (também clássico), sem precisar de import/export.
--}}
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function formatarPeriodo(periodoApuracao) {
    const str = String(periodoApuracao ?? '');
    return str.length === 6 ? `${str.slice(4, 6)}/${str.slice(0, 4)}` : str;
}

function formatarDataHora(valor) {
    const str = String(valor ?? '');
    if (str.length !== 14) return str || '—';
    return `${str.slice(6, 8)}/${str.slice(4, 6)}/${str.slice(0, 4)} ${str.slice(8, 10)}:${str.slice(10, 12)}:${str.slice(12, 14)}`;
}

function formatarData8(valor) {
    const str = String(valor ?? '');
    if (str.length !== 8) return str || '—';
    return `${str.slice(6, 8)}/${str.slice(4, 6)}/${str.slice(0, 4)}`;
}

function formatarMoeda(valor) {
    if (valor === null || valor === undefined) return '—';
    return Number(valor).toFixed(2).replace('.', ',');
}

/**
 * Cada tela do Simples Nacional (exceto o hub e a própria tela de configuração)
 * exige a API SERPRO configurada — antes cada card ficava "hidden" até
 * configurar (padrão antigo, uma página só); agora, como cada módulo é sua
 * própria página, cada uma se auto-protege chamando isso no load.
 */
async function protegerComConfigSerpro(containerId) {
    const container = document.getElementById(containerId);

    try {
        const resp = await fetch('{{ route('simples-nacional.configuracao.get') }}', { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        if (!data.configurado || !data.arquivo_ok) {
            container.innerHTML = `
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-700 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    @if(auth()->user()?->canAcessarConfiguracaoApi())
                        <span>A API Integra Contador (SERPRO) ainda não está configurada. <a href="{{ route('simples-nacional.configuracao.tela') }}" class="underline text-brand">Configurar agora</a>.</span>
                    @else
                        <span>A API Integra Contador (SERPRO) ainda não está configurada. Solicite ao TI para configurar.</span>
                    @endif
                </div>
            `;
        }
    } catch (e) {
        container.innerHTML = `
            <div class="text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">
                Erro ao verificar a configuração da API. Recarregue a página.
            </div>
        `;
    }
}
</script>
