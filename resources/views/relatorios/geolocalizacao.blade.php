@extends('layouts.internal')

@section('title', 'Relatório de Geolocalização — WR Assessoria')

@section('content')
    <div class="py-6 px-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-map-location-dot"></i> Geolocalização de Clientes</h1>
                <p class="text-gray-700">Distribuição de clientes por estado e cidade.</p>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('relatorios.geolocalizacao') }}" id="form-geo"
              class="bg-white rounded shadow px-4 py-3 mb-6 flex flex-wrap gap-3 items-end">

            <div>
                <label class="block text-xs text-gray-500 mb-1">Status</label>
                <select name="status" onchange="document.getElementById('form-geo').submit()"
                        class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    <option value="ativo"   @selected($statusFiltro === 'ativo')>Ativo</option>
                    <option value="inativo" @selected($statusFiltro === 'inativo')>Inativo</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Estado (UF)</label>
                <select name="estado" id="select-estado" onchange="document.getElementById('form-geo').submit()"
                        class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos os estados</option>
                    @foreach($porEstado as $item)
                        <option value="{{ $item->estado }}" @selected($estadoFiltro === $item->estado)>
                            {{ $item->estado }} ({{ $item->total }})
                        </option>
                    @endforeach
                </select>
            </div>

            @if($estadoFiltro && $cidadesDoEstado->isNotEmpty())
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cidade</label>
                <select name="cidade" onchange="document.getElementById('form-geo').submit()"
                        class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todas as cidades</option>
                    @foreach($cidadesDoEstado as $cid)
                        <option value="{{ $cid->cidade }}" @selected($cidadeFiltro === $cid->cidade)>
                            {{ $cid->cidade }} ({{ $cid->total }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($estadoFiltro || $cidadeFiltro || ($statusFiltro && $statusFiltro !== 'ativo'))
            <div>
                <a href="{{ route('relatorios.geolocalizacao') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded text-gray-600 hover:bg-gray-50 bg-white no-underline">
                    <i class="fa-solid fa-xmark"></i> Limpar filtros
                </a>
            </div>
            @endif
        </form>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total de clientes</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalClientes }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Com localização</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ $totalComLocalizacao }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sem localização</p>
                <p class="mt-1 text-3xl font-bold text-gray-400">{{ $totalSemLocalizacao }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                    @if($estadoFiltro || $cidadeFiltro)
                        Resultado do filtro
                    @else
                        Estados cadastrados
                    @endif
                </p>
                <p class="mt-1 text-3xl font-bold text-brand">
                    @if($estadoFiltro || $cidadeFiltro)
                        {{ $clientesFiltrados->count() }}
                    @else
                        {{ $porEstado->count() }}
                    @endif
                </p>
            </div>
        </div>

        {{-- Mapa do Brasil --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-700">
                    <i class="fa-solid fa-map mr-1 text-brand"></i> Mapa de distribuição
                    @if($estadoFiltro) — {{ $estadoFiltro }} @endif
                </h2>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                    <i class="fa-solid fa-circle-info"></i>
                    @if($estadoFiltro)
                        Clique em uma cidade para filtrar
                    @else
                        Clique em um estado para filtrar
                    @endif
                </span>
            </div>
            <div id="mapa-clientes" style="height:460px;border-radius:0.5rem;z-index:1;"></div>
        </div>

        @if(!$estadoFiltro && !$cidadeFiltro)
        {{-- Gráfico e tabela de estados --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-chart-bar mr-1 text-brand"></i> Clientes por estado
                </h2>
                @if($porEstado->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum cliente com estado cadastrado.</p>
                @else
                    <div style="position:relative;height:280px">
                        <canvas id="chartPorEstado"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-table mr-1 text-brand"></i> Ranking por estado
                </h2>
                @if($porEstado->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left py-2 pr-4 font-medium text-gray-500">#</th>
                                    <th class="text-left py-2 pr-4 font-medium text-gray-500">Estado</th>
                                    <th class="text-right py-2 font-medium text-gray-500">Clientes</th>
                                    <th class="text-right py-2 pl-4 font-medium text-gray-500">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($porEstado as $i => $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 text-gray-400 text-xs">{{ $i + 1 }}</td>
                                        <td class="py-2 pr-4">
                                            <a href="{{ route('relatorios.geolocalizacao', ['estado' => $item->estado, 'status' => $statusFiltro]) }}"
                                               class="font-semibold text-brand hover:underline no-underline">
                                                {{ $item->estado }}
                                            </a>
                                        </td>
                                        <td class="py-2 text-right font-bold text-gray-800">{{ $item->total }}</td>
                                        <td class="py-2 pl-4 text-right text-gray-500">
                                            @if($totalComLocalizacao > 0)
                                                {{ number_format(($item->total / $totalComLocalizacao) * 100, 1) }}%
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @endif

        @if($estadoFiltro && $cidadesDoEstado->isNotEmpty() && !$cidadeFiltro)
        {{-- Gráfico por cidade do estado selecionado --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                <i class="fa-solid fa-city mr-1 text-brand"></i>
                Clientes por cidade — {{ $estadoFiltro }}
            </h2>
            <div style="position:relative;height:280px">
                <canvas id="chartPorCidade"></canvas>
            </div>
        </div>
        @endif

        {{-- Tabela de clientes filtrados --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                <i class="fa-solid fa-users mr-1 text-brand"></i>
                Clientes
                @if($estadoFiltro || $cidadeFiltro)
                    —
                    @if($cidadeFiltro) {{ $cidadeFiltro }}, @endif
                    @if($estadoFiltro) {{ $estadoFiltro }} @endif
                @endif
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    {{ $clientesFiltrados->count() }}
                </span>
            </h2>

            @if($clientesFiltrados->isEmpty())
                <p class="text-sm text-gray-400 italic">Nenhum cliente encontrado para o filtro selecionado.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-2 pr-4 font-medium text-gray-500">Nome</th>
                                <th class="text-left py-2 pr-4 font-medium text-gray-500">Tipo</th>
                                <th class="text-left py-2 pr-4 font-medium text-gray-500">Regime</th>
                                <th class="text-left py-2 pr-4 font-medium text-gray-500">Cidade</th>
                                <th class="text-left py-2 pr-4 font-medium text-gray-500">UF</th>
                                <th class="text-left py-2 font-medium text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($clientesFiltrados as $cliente)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 pr-4 font-medium text-gray-800">
                                        <a href="{{ route('clientes.form.edit', $cliente->id) }}"
                                           class="text-brand hover:underline no-underline"
                                           data-modal-url="{{ route('clientes.form.edit', $cliente->id) }}">
                                            {{ $cliente->nome }}
                                        </a>
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">
                                        @if((string) $cliente->tipo === '1')
                                            <span class="inline-flex items-center gap-1 text-xs"><i class="fa-solid fa-building-user text-blue-500"></i> PJ</span>
                                        @elseif((string) $cliente->tipo === '0')
                                            <span class="inline-flex items-center gap-1 text-xs"><i class="fa-solid fa-user text-emerald-500"></i> PF</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600 text-xs">{{ $cliente->regime_tributario ?: '—' }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $cliente->cidade ?: '—' }}</td>
                                    <td class="py-2 pr-4 text-gray-600 font-semibold">{{ $cliente->estado ?: '—' }}</td>
                                    <td class="py-2">
                                        @if($cliente->status === 'ativo')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Ativo</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inativo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    // ── Coordenadas dos centróides de todos os estados brasileiros ─────
    const estadoCoordenadas = {
        'AC': [-9.0238,  -70.8120], 'AL': [-9.5713,  -36.7820],
        'AM': [-4.4197,  -63.5806], 'AP': [1.4102,   -51.7703],
        'BA': [-12.5086, -41.7007], 'CE': [-5.4984,  -39.3206],
        'DF': [-15.7801, -47.9292], 'ES': [-19.1834, -40.3089],
        'GO': [-16.6869, -49.2648], 'MA': [-5.4924,  -45.2938],
        'MG': [-18.5122, -44.5550], 'MS': [-20.7722, -54.7852],
        'MT': [-12.6819, -56.9211], 'PA': [-5.4976,  -52.4656],
        'PB': [-7.2399,  -36.7819], 'PE': [-8.8137,  -36.9541],
        'PI': [-8.0780,  -42.8016], 'PR': [-25.2521, -52.0215],
        'RJ': [-22.9068, -43.1729], 'RN': [-5.8127,  -36.2031],
        'RO': [-11.5057, -63.5806], 'RR': [1.9903,   -61.3302],
        'RS': [-30.0346, -51.2177], 'SC': [-27.5954, -48.5480],
        'SE': [-10.9472, -37.0731], 'SP': [-23.5505, -46.6333],
        'TO': [-10.1753, -48.2982],
    };

    // ── Inicializar o mapa ─────────────────────────────────────────────
    const map = L.map('mapa-clientes', {
        center: [-14.5, -52.5],
        zoom: {{ $estadoFiltro ? 6 : 4 }},
        minZoom: 3,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18,
    }).addTo(map);

    // ── Dados passados pelo PHP ────────────────────────────────────────
    const porEstado    = @json($porEstado);
    const estadoFiltro = @json($estadoFiltro);
    const cidadeFiltro = @json($cidadeFiltro);
    const statusFiltro = @json($statusFiltro);
    const cidadesDoEstado = @json($cidadesDoEstado);

    const maxTotal = porEstado.length ? Math.max(...porEstado.map(e => e.total)) : 1;

    function buildFilterUrl(params) {
        const base = '{{ route('relatorios.geolocalizacao') }}';
        const qs = new URLSearchParams(params).toString();
        return qs ? base + '?' + qs : base;
    }

    // ── Marcadores por estado ──────────────────────────────────────────
    if (!estadoFiltro) {
        porEstado.forEach(item => {
            const coords = estadoCoordenadas[item.estado];
            if (!coords) { return; }

            const radius = Math.max(18, Math.round(10 + (item.total / maxTotal) * 40));
            const marker = L.circleMarker(coords, {
                radius,
                color: '#fff',
                weight: 2,
                fillColor: '#3b82f6',
                fillOpacity: 0.75,
            }).addTo(map);

            const url = buildFilterUrl({ estado: item.estado, status: statusFiltro || '' });
            marker.bindPopup(
                `<div class="text-center" style="min-width:120px">
                    <p class="font-bold text-lg text-gray-800 mb-1">${item.estado}</p>
                    <p class="text-sm text-gray-500">${item.total} cliente${item.total !== 1 ? 's' : ''}</p>
                    <a href="${url}" class="mt-2 inline-block text-xs text-blue-600 hover:underline">Ver detalhes →</a>
                </div>`
            );

            marker.bindTooltip(`<b>${item.estado}</b>: ${item.total}`, { direction: 'top', offset: [0, -5] });
        });
    }

    // ── Marcadores por cidade (quando estado está filtrado) ────────────
    if (estadoFiltro && cidadesDoEstado.length > 0) {
        const cacheKey = `geo_cities_${estadoFiltro}`;
        const cached  = sessionStorage.getItem(cacheKey);

        if (cached) {
            renderCidadeMarkers(JSON.parse(cached));
        } else {
            geocodeCidades(cidadesDoEstado, estadoFiltro).then(result => {
                sessionStorage.setItem(cacheKey, JSON.stringify(result));
                renderCidadeMarkers(result);
            });
        }

        // Centralizar no estado selecionado
        const centroEstado = estadoCoordenadas[estadoFiltro];
        if (centroEstado) {
            map.setView(centroEstado, 6);
        }
    } else if (estadoFiltro) {
        const centroEstado = estadoCoordenadas[estadoFiltro];
        if (centroEstado) {
            map.setView(centroEstado, 6);
            L.circleMarker(centroEstado, {
                radius: 20,
                color: '#fff',
                weight: 2,
                fillColor: '#3b82f6',
                fillOpacity: 0.75,
            }).addTo(map).bindPopup(
                `<div class="text-center" style="min-width:120px">
                    <p class="font-bold text-lg text-gray-800 mb-1">${estadoFiltro}</p>
                    <p class="text-sm text-gray-500">Sem dados de cidade</p>
                </div>`
            );
        }
    }

    // ── Geocodificar cidades via Nominatim (1 req/s, sem API key) ──────
    async function geocodeCidades(cidades, estado) {
        const resultado = [];
        for (const cid of cidades) {
            try {
                const q = encodeURIComponent(`${cid.cidade}, ${estado}, Brasil`);
                const resp = await fetch(
                    `https://nominatim.openstreetmap.org/search?q=${q}&format=json&limit=1&countrycodes=br`,
                    { headers: { 'Accept-Language': 'pt-BR' } }
                );
                const data = await resp.json();
                if (data.length > 0) {
                    resultado.push({
                        cidade: cid.cidade,
                        total:  cid.total,
                        lat:    parseFloat(data[0].lat),
                        lon:    parseFloat(data[0].lon),
                    });
                }
            } catch (_) { /* ignora erros de geocodificação */ }
            await new Promise(r => setTimeout(r, 1100)); // respeita limite 1 req/s
        }
        return resultado;
    }

    function renderCidadeMarkers(cidades) {
        const maxCid = cidades.length ? Math.max(...cidades.map(c => c.total)) : 1;
        cidades.forEach(cid => {
            const radius = Math.max(14, Math.round(8 + (cid.total / maxCid) * 30));
            const marker = L.circleMarker([cid.lat, cid.lon], {
                radius,
                color: '#fff',
                weight: 2,
                fillColor: '#10b981',
                fillOpacity: 0.8,
            }).addTo(map);

            const url = buildFilterUrl({ estado: estadoFiltro, cidade: cid.cidade, status: statusFiltro || '' });
            marker.bindPopup(
                `<div class="text-center" style="min-width:130px">
                    <p class="font-bold text-base text-gray-800 mb-1">${cid.cidade}</p>
                    <p class="text-sm text-gray-500">${cid.total} cliente${cid.total !== 1 ? 's' : ''}</p>
                    <a href="${url}" class="mt-2 inline-block text-xs text-blue-600 hover:underline">Ver clientes →</a>
                </div>`
            );
            marker.bindTooltip(`<b>${cid.cidade}</b>: ${cid.total}`, { direction: 'top', offset: [0, -5] });
        });
    }

    const palette = [
        '#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6',
        '#ec4899','#06b6d4','#84cc16','#f97316','#6366f1',
        '#14b8a6','#a855f7','#f43f5e','#0ea5e9','#22c55e',
    ];

    @if(!$estadoFiltro && !$cidadeFiltro && $porEstado->isNotEmpty())
    new Chart(document.getElementById('chartPorEstado'), {
        type: 'bar',
        data: {
            labels: @json($porEstado->pluck('estado')),
            datasets: [{
                label: 'Clientes',
                data: @json($porEstado->pluck('total')),
                backgroundColor: palette.slice(0, {{ $porEstado->count() }}),
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
            }
        }
    });
    @endif

    @if($estadoFiltro && $cidadesDoEstado->isNotEmpty() && !$cidadeFiltro)
    new Chart(document.getElementById('chartPorCidade'), {
        type: 'bar',
        data: {
            labels: @json($cidadesDoEstado->pluck('cidade')),
            datasets: [{
                label: 'Clientes',
                data: @json($cidadesDoEstado->pluck('total')),
                backgroundColor: palette.slice(0, {{ $cidadesDoEstado->count() }}),
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
            }
        }
    });
    @endif
</script>
@endpush
