@extends('layouts.internal')

@section('title', 'Produtos de Precificação — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-calculator"></i> Produtos de Precificação</h1>
                <p class="text-gray-700 dark:text-gray-300">Selecione um cliente para ver e gerenciar o catálogo de produtos dele.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded shadow p-4 mb-6 relative">
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Cliente</label>
            <input type="text" id="busca-cliente" autocomplete="off"
                   placeholder="Digite o nome ou CPF/CNPJ do cliente..."
                   value="{{ $cliente->nome ?? '' }}"
                   class="block w-full max-w-md border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
            <ul id="lista-resultados-cliente" class="hidden absolute z-10 mt-1 w-full max-w-md bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded shadow max-h-56 overflow-y-auto"></ul>
        </div>

        @if($cliente)
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Produtos de {{ $cliente->nome }}</h2>
                <div class="flex gap-2">
                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded focus:outline-none hover:bg-gray-50 dark:hover:bg-slate-600"
                            data-modal-url="{{ route('precificacao.produtos.import.form', ['cliente_id' => $cliente->id]) }}">
                        <i class="fa-solid fa-file-import"></i>
                    </button>
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                            data-modal-url="{{ route('precificacao.produtos.form.create', ['cliente_id' => $cliente->id]) }}">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">NCM / CEST</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Custo unitário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Preço sugerido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Margem</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse($resumo as $item)
                            @php $produto = $item['produto']; $resultado = $item['resultado']; @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm whitespace-nowrap">
                                    <a href="{{ route('precificacao.produtos.show', $produto->id) }}" class="text-brand hover:text-brand/80">{{ $produto->nome }}</a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $produto->ncm }} @if($produto->cest) / {{ $produto->cest }} @endif</td>
                                @if($resultado)
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">R$ {{ number_format($resultado->custoUnitario, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">R$ {{ number_format($resultado->precoVenda, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <span class="{{ $resultado->margemContribuicaoValor >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($resultado->margemContribuicaoPercentual, 2, ',', '.') }}%
                                        </span>
                                    </td>
                                @else
                                    <td class="px-6 py-4 text-sm text-gray-400" colspan="3">Sem cenário cadastrado.</td>
                                @endif
                                <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                    <a href="{{ route('precificacao.produtos.show', $produto->id) }}" class="text-gray-500 hover:text-brand focus:outline-none">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum produto cadastrado para este cliente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @push('scripts')
    <script type="module">
    (function () {
        const buscaInput = document.getElementById('busca-cliente');
        const lista = document.getElementById('lista-resultados-cliente');
        let timer = null;

        buscaInput.addEventListener('input', function () {
            const q = buscaInput.value.trim();
            clearTimeout(timer);

            if (q.length < 2) {
                lista.classList.add('hidden');
                lista.innerHTML = '';
                return;
            }

            timer = setTimeout(function () {
                fetch('{{ route('clientes.busca') }}?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => r.json())
                    .then(function (data) {
                        lista.innerHTML = '';
                        if (!data.length) {
                            lista.classList.add('hidden');
                            return;
                        }
                        data.forEach(function (c) {
                            const li = document.createElement('li');
                            li.className = 'px-3 py-2 text-sm text-gray-800 dark:text-slate-100 hover:bg-gray-50 dark:hover:bg-slate-600 cursor-pointer';
                            li.innerHTML = '<span class="font-medium">' + c.nome + '</span>' +
                                (c.cpfcnpj ? ' <span class="text-xs text-gray-400 dark:text-slate-400">' + c.cpfcnpj + '</span>' : '');
                            li.addEventListener('click', function () {
                                window.location = '{{ route('precificacao.produtos') }}?cliente_id=' + c.id;
                            });
                            lista.appendChild(li);
                        });
                        lista.classList.remove('hidden');
                    });
            }, 300);
        });

        document.addEventListener('click', function (e) {
            if (!lista.contains(e.target) && e.target !== buscaInput) {
                lista.classList.add('hidden');
            }
        });
    })();
    </script>
    @endpush
@endsection
