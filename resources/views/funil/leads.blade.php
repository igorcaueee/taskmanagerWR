@extends('layouts.internal')

@section('title', 'Leads — WR Assessoria')

@section('content')
    <div class="w-full mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-user-plus"></i> Leads</h1>
                <p class="text-gray-700 dark:text-gray-300">Visualize e gerencie todos os leads do funil de vendas.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('funil.captura') }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded hover:bg-gray-50 dark:hover:bg-slate-600 text-sm no-underline">
                    <i class="fa-solid fa-globe"></i> Form. Público
                </a>
                <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 text-sm"
                        data-modal-url="{{ route('leads.form.create') }}">
                    <i class="fa-solid fa-plus"></i> Novo Lead
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded shadow">
            {{-- Filters --}}
            <form method="GET" action="{{ route('leads.index') }}" id="form-filtros-leads"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Nome, empresa, e-mail..."
                           onchange="document.getElementById('form-filtros-leads').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Etapa</label>
                    <select name="etapa_id" onchange="document.getElementById('form-filtros-leads').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todas</option>
                        @foreach($etapas as $etapa)
                            <option value="{{ $etapa->id }}" @selected(request('etapa_id') == $etapa->id)>{{ $etapa->nome }}</option>
                        @endforeach
                    </select>
                </div>
                @if($podeVerTodos)
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Responsável</label>
                        <select name="responsavel_id" onchange="document.getElementById('form-filtros-leads').submit()"
                                class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                            <option value="">Todos</option>
                            @foreach($usuarios as $usr)
                                <option value="{{ $usr->id }}" @selected(request('responsavel_id') == $usr->id)>{{ $usr->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if(request()->hasAny(['busca', 'etapa_id', 'responsavel_id']))
                    <div class="flex items-end">
                        <a href="{{ route('leads.index') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 no-underline border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600">
                            <i class="fa-solid fa-xmark"></i> Limpar
                        </a>
                    </div>
                @endif
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-xs">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Empresa</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">E-mail</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Telefone</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Etapa</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Responsável</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Cadastrado em</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer"
                            onclick="window.location='{{ route('leads.show', $lead->id) }}'">
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 font-medium">{{ $lead->nome }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ $lead->empresa ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ $lead->email ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $lead->telefone ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs">
                                @if($lead->etapaFunil)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium"
                                          style="background-color: {{ $lead->etapaFunil->cor }}1a; color: {{ $lead->etapaFunil->cor }}">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $lead->etapaFunil->cor }}"></span>
                                        {{ $lead->etapaFunil->nome }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ $lead->responsavel?->nome ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $lead->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2 text-xs text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <a href="{{ route('leads.show', $lead->id) }}"
                                   class="text-brand hover:text-brand/70 no-underline mr-2"
                                   title="Ver detalhes">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <button type="button"
                                        class="text-blue-500 hover:text-blue-700 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 mr-2"
                                        data-modal-url="{{ route('leads.form.edit', $lead->id) }}"
                                        title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                <i class="fa-solid fa-user-slash text-2xl mb-2 block"></i>
                                Nenhum lead encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($leads->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
                    {{ $leads->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
