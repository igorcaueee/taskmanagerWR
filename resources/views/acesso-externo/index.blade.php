@extends('layouts.internal')

@section('title', 'Acesso Externo — WR Assessoria')

@section('content')
<div class="py-6 px-6 max-w-6xl mx-auto">

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">
            <i class="fa-solid fa-shield-halved mr-2 text-[#0084AA]"></i> Controle de Acesso Externo
        </h1>
    </div>

    {{-- ─── ALERTA DE IPs SUSPEITOS ───────────────────────────────────────────── --}}
    @if ($ipsSuspeitos->isNotEmpty())
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 rounded-xl p-4 mb-8">
        <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            Possível ameaça: os IPs abaixo tiveram 5 ou mais tentativas de acesso bloqueadas nas últimas 24 horas.
        </p>
        <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
            @foreach ($ipsSuspeitos as $s)
                <li><span class="font-mono font-medium">{{ $s->ip }}</span> — {{ $s->total }} tentativas</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ─── REDES PERMITIDAS ──────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#1e293b] rounded-xl shadow-sm border border-gray-200 dark:border-[#334155] mb-8">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-[#334155] flex items-center gap-2">
            <i class="fa-solid fa-network-wired text-[#0084AA]"></i>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Redes Permitidas (IP / CIDR)</h2>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-4">
                Usuários que acessarem o sistema a partir de um IP pertencente a estas faixas terão acesso liberado automaticamente.
                Use o formato <code class="bg-gray-100 dark:bg-slate-700 px-1 rounded text-xs">192.168.88.0/24</code> para faixas CIDR ou apenas
                <code class="bg-gray-100 dark:bg-slate-700 px-1 rounded text-xs">201.23.45.67</code> para um IP fixo único.
            </p>

            @if ($redes->isEmpty())
                <p class="text-sm text-amber-600 dark:text-amber-400 font-medium mb-4">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Nenhuma rede cadastrada — acesso externo está bloqueado para todos os não-diretores sem liberação individual.
                </p>
            @else
                <table class="w-full text-sm mb-6">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-100 dark:border-slate-700">
                            <th class="pb-2 pr-4">IP / CIDR</th>
                            <th class="pb-2 pr-4">Descrição</th>
                            <th class="pb-2 pr-4">Status</th>
                            <th class="pb-2 pr-4">Cadastrado por</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($redes as $rede)
                            <tr class="border-b border-gray-50 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/20">
                                <td class="py-3 pr-4 font-mono font-medium text-gray-800 dark:text-slate-200">{{ $rede->cidr }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-slate-400">{{ $rede->descricao ?? '—' }}</td>
                                <td class="py-3 pr-4">
                                    @if ($rede->ativo)
                                        <span class="inline-flex items-center gap-1 text-xs text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-0.5 rounded-full">
                                            <i class="fa-solid fa-circle text-[6px]"></i> Ativa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-slate-500 bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded-full">
                                            <i class="fa-solid fa-circle text-[6px]"></i> Inativa
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-gray-500 dark:text-slate-400 text-xs">{{ $rede->criadoPor?->nome ?? '—' }}</td>
                                <td class="py-3">
                                    <div class="flex items-center gap-2 justify-end">
                                        <form method="POST" action="{{ route('acesso-externo.redes.toggle', $rede->id) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                class="text-xs px-3 py-1 rounded border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent transition"
                                                title="{{ $rede->ativo ? 'Desativar' : 'Ativar' }}">
                                                {{ $rede->ativo ? 'Desativar' : 'Ativar' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('acesso-externo.redes.destroy', $rede->id) }}" class="form-delete-rede">
                                            @csrf @method('DELETE')
                                            <button type="button"
                                                onclick="confirmarExclusaoRede(this)"
                                                class="text-xs px-3 py-1 rounded border border-gray-300 dark:border-slate-600 text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent transition">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Formulário para adicionar rede --}}
            <form method="POST" action="{{ route('acesso-externo.redes.store') }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">IP / CIDR <span class="text-red-500">*</span></label>
                    <input type="text" name="cidr" placeholder="192.168.88.0/24" required
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0084AA]/30 focus:border-[#0084AA] w-48"
                        value="{{ old('cidr') }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Descrição</label>
                    <input type="text" name="descricao" placeholder="Ex: Rede LAN da WR"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0084AA]/30 focus:border-[#0084AA] w-56"
                        value="{{ old('descricao') }}">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#0084AA] hover:bg-[#006e8e] text-white text-sm font-medium rounded-lg transition">
                    <i class="fa-solid fa-plus"></i> Adicionar Rede
                </button>
            </form>
        </div>
    </div>

    {{-- ─── LIBERAÇÕES ATIVAS ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#1e293b] rounded-xl shadow-sm border border-gray-200 dark:border-[#334155] mb-8">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-[#334155] flex items-center gap-2">
            <i class="fa-solid fa-user-check text-green-500"></i>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Liberações de Acesso Externo</h2>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-4">
                Usuários abaixo podem acessar o sistema de qualquer rede, independentemente do IP. Liberações expiradas são bloqueadas automaticamente no próximo acesso.
            </p>

            @if ($liberacoes->isEmpty())
                <p class="text-sm text-gray-400 dark:text-slate-500 mb-6">Nenhuma liberação ativa no momento.</p>
            @else
                <table class="w-full text-sm mb-6">
                    <thead>
                        <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-100 dark:border-slate-700">
                            <th class="pb-2 pr-4">Usuário</th>
                            <th class="pb-2 pr-4">Cargo</th>
                            <th class="pb-2 pr-4">Expira em</th>
                            <th class="pb-2 pr-4">Motivo</th>
                            <th class="pb-2 pr-4">Liberado por</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($liberacoes as $lib)
                            <tr class="border-b border-gray-50 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/20">
                                <td class="py-3 pr-4 font-medium text-gray-800 dark:text-slate-200">{{ $lib->usuario->nome }}</td>
                                <td class="py-3 pr-4 text-gray-500 dark:text-slate-400 text-xs capitalize">{{ $lib->usuario->cargo }}</td>
                                <td class="py-3 pr-4">
                                    @if ($lib->expira_em)
                                        @if ($lib->expira_em->isPast())
                                            <span class="text-xs text-red-500 font-medium">
                                                <i class="fa-solid fa-clock-rotate-left mr-1"></i>Expirado: {{ $lib->expira_em->format('d/m/Y H:i') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-amber-600 dark:text-amber-400">
                                                <i class="fa-regular fa-clock mr-1"></i>{{ $lib->expira_em->format('d/m/Y H:i') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                                            <i class="fa-solid fa-infinity mr-1"></i>Sem prazo
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-gray-500 dark:text-slate-400 text-xs">{{ $lib->motivo ?? '—' }}</td>
                                <td class="py-3 pr-4 text-gray-500 dark:text-slate-400 text-xs">{{ $lib->liberadoPor->nome }}</td>
                                <td class="py-3">
                                    <form method="POST" action="{{ route('acesso-externo.liberacoes.revogar', $lib->id) }}" class="form-revogar">
                                        @csrf @method('DELETE')
                                        <button type="button"
                                            onclick="confirmarRevogacao(this)"
                                            class="text-xs px-3 py-1 rounded border border-gray-300 dark:border-slate-600 text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent transition">
                                            Revogar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Formulário para nova liberação --}}
            <form method="POST" action="{{ route('acesso-externo.liberacoes.store') }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Usuário <span class="text-red-500">*</span></label>
                    <select name="usuario_id" required
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0084AA]/30 focus:border-[#0084AA] w-52">
                        <option value="">— Selecione —</option>
                        @foreach ($usuarios as $u)
                            <option value="{{ $u->id }}" {{ old('usuario_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->nome }} ({{ $u->cargo }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">
                        Expira em <span class="text-gray-400">(vazio = sem prazo)</span>
                    </label>
                    <input type="datetime-local" name="expira_em"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0084AA]/30 focus:border-[#0084AA]"
                        value="{{ old('expira_em') }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Motivo</label>
                    <input type="text" name="motivo" placeholder="Ex: Trabalho remoto aprovado"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#0084AA]/30 focus:border-[#0084AA] w-56"
                        value="{{ old('motivo') }}">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fa-solid fa-user-plus"></i> Liberar Acesso
                </button>
            </form>
        </div>
    </div>

    {{-- ─── HISTÓRICO DE REVOGAÇÕES ──────────────────────────────────────────── --}}
    @if ($historico->isNotEmpty())
    <div class="bg-white dark:bg-[#1e293b] rounded-xl shadow-sm border border-gray-200 dark:border-[#334155]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-[#334155] flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Histórico de Liberações Revogadas</h2>
        </div>
        <div class="p-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-100 dark:border-slate-700">
                        <th class="pb-2 pr-4">Usuário</th>
                        <th class="pb-2 pr-4">Motivo</th>
                        <th class="pb-2 pr-4">Liberado por</th>
                        <th class="pb-2">Revogado em</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historico as $h)
                        <tr class="border-b border-gray-50 dark:border-slate-700/50 text-gray-500 dark:text-slate-400">
                            <td class="py-2 pr-4">{{ $h->usuario->nome }}</td>
                            <td class="py-2 pr-4 text-xs">{{ $h->motivo ?? '—' }}</td>
                            <td class="py-2 pr-4 text-xs">{{ $h->liberadoPor->nome }}</td>
                            <td class="py-2 text-xs">{{ $h->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ─── TENTATIVAS DE ACESSO BLOQUEADAS ───────────────────────────────────── --}}
    <div class="bg-white dark:bg-[#1e293b] rounded-xl shadow-sm border border-gray-200 dark:border-[#334155] mt-8">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-[#334155] flex items-center gap-2">
            <i class="fa-solid fa-user-secret text-red-500"></i>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Tentativas de Acesso Bloqueadas</h2>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-4">
                Toda vez que um usuário tenta acessar o sistema de fora da rede permitida (e sem liberação ativa), o acesso é negado
                e a tentativa fica registrada aqui. Mostrando as 100 mais recentes.
            </p>

            @if ($tentativas->isEmpty())
                <p class="text-sm text-gray-400 dark:text-slate-500">Nenhuma tentativa bloqueada registrada até o momento.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500 dark:text-slate-400 border-b border-gray-100 dark:border-slate-700">
                                <th class="pb-2 pr-4">Usuário</th>
                                <th class="pb-2 pr-4">IP</th>
                                <th class="pb-2 pr-4">URL acessada</th>
                                <th class="pb-2 pr-4">Navegador / Dispositivo</th>
                                <th class="pb-2">Data/Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tentativas as $t)
                                <tr class="border-b border-gray-50 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/20">
                                    <td class="py-3 pr-4 font-medium text-gray-800 dark:text-slate-200">{{ $t->usuario?->nome ?? '—' }}</td>
                                    <td class="py-3 pr-4 font-mono text-gray-700 dark:text-slate-300">{{ $t->ip }}</td>
                                    <td class="py-3 pr-4 text-gray-500 dark:text-slate-400 text-xs break-all max-w-xs">{{ $t->url ?? '—' }}</td>
                                    <td class="py-3 pr-4 text-gray-500 dark:text-slate-400 text-xs break-all max-w-xs">{{ $t->user_agent ?? '—' }}</td>
                                    <td class="py-3 text-gray-500 dark:text-slate-400 text-xs whitespace-nowrap">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: '{{ session('success') }}',
            confirmButtonColor: '#0084aa',
        });
    @endif

    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: '{{ $errors->first() }}',
            confirmButtonColor: '#0084aa',
        });
    @endif

    function confirmarExclusaoRede(btn) {
        Swal.fire({
            icon: 'warning',
            title: 'Remover rede?',
            text: 'A faixa de IP será removida permanentemente. Usuários dessa rede precisarão de liberação individual.',
            showCancelButton: true,
            confirmButtonText: 'Sim, remover',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
        }).then(result => {
            if (result.isConfirmed) {
                btn.closest('form').submit();
            }
        });
    }

    function confirmarRevogacao(btn) {
        Swal.fire({
            icon: 'warning',
            title: 'Revogar liberação?',
            text: 'O usuário perderá o acesso externo imediatamente no próximo request.',
            showCancelButton: true,
            confirmButtonText: 'Sim, revogar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
        }).then(result => {
            if (result.isConfirmed) {
                btn.closest('form').submit();
            }
        });
    }
</script>
@endsection
