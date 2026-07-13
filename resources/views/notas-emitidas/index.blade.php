@extends('layouts.internal')

@section('title', 'Contador de Notas — WR Assessoria')

@section('content')
    <div class="max-w-5xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-file-invoice"></i> Contador de Notas</h1>
                <p class="text-gray-700 dark:text-gray-300">Controle de notas fiscais emitidas por cliente.</p>
            </div>
            <div class="flex items-center gap-6">
                <a href="{{ route('notas-emitidas.emitentes.index') }}" class="text-sm text-brand hover:text-brand/80 no-underline">
                    <i class="fa-regular fa-address-card"></i> Gerenciar cadastro
                </a>
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total no período</p>
                    <p class="text-3xl font-bold text-brand" id="total-geral">{{ $totalGeral }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded shadow">
            {{-- Filtros --}}
            <form method="GET" action="{{ route('notas-emitidas.index') }}" id="form-filtros-notas"
                  class="flex flex-wrap gap-3 items-end px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Período</label>
                    <select name="periodo" id="select-periodo"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="hoje"          @selected($periodo === 'hoje')>Hoje</option>
                        <option value="semana"        @selected($periodo === 'semana')>Esta semana</option>
                        <option value="mes"           @selected($periodo === 'mes')>Este mês</option>
                        <option value="personalizado" @selected($periodo === 'personalizado')>Personalizado</option>
                    </select>
                </div>

                <div id="datas-personalizadas" class="{{ $periodo === 'personalizado' ? 'flex' : 'hidden' }} gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">De</label>
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}"
                               class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Até</label>
                        <input type="date" name="data_fim" value="{{ $dataFim }}"
                               class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    </div>
                </div>

                <div class="ml-auto">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ $busca }}"
                           placeholder="Buscar por nome..."
                           onchange="document.getElementById('form-filtros-notas').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-64">
                </div>
            </form>

            @if($periodo !== 'hoje')
                <div class="px-4 py-2 text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-900/40">
                    <i class="fa-solid fa-circle-info"></i> Você está consultando um período passado. Lançamentos de notas só podem ser feitos no dia de hoje.
                </div>
            @endif

            <ul class="divide-y divide-gray-200 dark:divide-slate-700" id="lista-emitentes">
                @forelse($emitentes as $emitente)
                    <li class="flex items-center justify-between px-4 py-3" data-emitente-id="{{ $emitente->id }}">
                        <span class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $emitente->nome_exibicao }}</span>

                        <div class="flex items-center gap-3">
                            <span class="w-10 text-center text-lg font-semibold text-gray-900 dark:text-slate-100 contador-valor">{{ $contagens[$emitente->id] ?? 0 }}</span>

                            @if($periodo === 'hoje')
                                <button type="button"
                                        class="btn-nota w-8 h-8 flex items-center justify-center rounded border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-300 bg-transparent hover:bg-gray-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-0 appearance-none"
                                        data-acao="estornar" data-emitente-id="{{ $emitente->id }}">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <button type="button"
                                        class="btn-nota w-8 h-8 flex items-center justify-center rounded bg-brand text-white hover:bg-brand/80 border-0 focus:outline-none focus:ring-0 appearance-none"
                                        data-acao="incrementar" data-emitente-id="{{ $emitente->id }}">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-gray-500 dark:text-slate-400">
                        Nenhum cadastro encontrado.
                        <a href="{{ route('notas-emitidas.emitentes.index') }}" class="text-brand hover:text-brand/80">Cadastre o primeiro cliente de nota.</a>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.getElementById('select-periodo').addEventListener('change', function () {
        document.getElementById('datas-personalizadas').classList.toggle('hidden', this.value !== 'personalizado');
        if (this.value !== 'personalizado') {
            document.getElementById('form-filtros-notas').submit();
        }
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.btn-nota').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const emitenteId = btn.dataset.emitenteId;
            const acao = btn.dataset.acao;
            const url = acao === 'incrementar'
                ? '{{ route('notas-emitidas.store') }}'
                : '{{ route('notas-emitidas.estornar') }}';

            btn.disabled = true;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ emitente_id: emitenteId }),
                });

                if (!res.ok) { throw new Error(); }
                const data = await res.json();

                const linha = document.querySelector(`li[data-emitente-id="${emitenteId}"]`);
                const contadorEl = linha.querySelector('.contador-valor');
                const anterior = parseInt(contadorEl.textContent, 10);
                contadorEl.textContent = data.total;

                const totalGeralEl = document.getElementById('total-geral');
                totalGeralEl.textContent = parseInt(totalGeralEl.textContent, 10) + (data.total - anterior);
            } catch (e) {
                Swal.fire({
                    title: 'Não foi possível registrar',
                    text: 'Tente novamente em instantes.',
                    icon: 'error',
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Entendi',
                });
            } finally {
                btn.disabled = false;
            }
        });
    });
    </script>
    @endpush
@endsection
