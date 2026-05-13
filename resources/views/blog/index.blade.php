@extends('layouts.internal')

@section('title', 'Blog — WR Assessoria')

@section('content')
<div class="py-6 px-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-newspaper"></i> Blog</h1>
            <p class="text-gray-700 dark:text-gray-300">Gerencie os artigos publicados no site.</p>
        </div>
        <a href="{{ route('blog.admin.form.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 no-underline text-sm font-medium">
            <i class="fa-solid fa-plus"></i> Novo Artigo
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtros --}}
    <form method="GET" action="{{ route('blog.admin.index') }}" class="flex flex-wrap gap-3 mb-4">
        <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por título..."
               class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
        <select name="status" onchange="this.form.submit()"
                class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
            <option value="">Todos os status</option>
            <option value="rascunho"   @selected(request('status') === 'rascunho')>Rascunho</option>
            <option value="agendado"   @selected(request('status') === 'agendado')>Agendado</option>
            <option value="publicado"  @selected(request('status') === 'publicado')>Publicado</option>
        </select>
        <button type="submit" class="px-4 py-1.5 bg-brand text-white text-sm rounded hover:bg-brand/80 border-0">Filtrar</button>
        @if (request()->hasAny(['busca', 'status']))
            <a href="{{ route('blog.admin.index') }}" class="px-4 py-1.5 text-sm text-gray-600 dark:text-gray-300 rounded border border-gray-300 dark:border-slate-600 hover:bg-gray-100 dark:hover:bg-slate-700 no-underline">Limpar</a>
        @endif
    </form>

    <div class="bg-white dark:bg-slate-800 rounded shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-gray-300 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Título</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Publicação</th>
                    <th class="px-4 py-3 text-left">Autor</th>
                    <th class="px-4 py-3 text-left">Criado em</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse ($artigos as $artigo)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40">
                        <td class="px-4 py-3 text-gray-900 dark:text-slate-100 font-medium max-w-xs truncate">
                            {{ $artigo->titulo }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($artigo->status === 'publicado')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">
                                    <i class="fa-solid fa-circle text-[6px]"></i> Publicado
                                </span>
                            @elseif ($artigo->status === 'agendado')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300">
                                    <i class="fa-solid fa-circle text-[6px]"></i> Agendado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-600 text-gray-600 dark:text-gray-300">
                                    <i class="fa-solid fa-circle text-[6px]"></i> Rascunho
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $artigo->publicado_em?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $artigo->autor?->nome ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $artigo->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($artigo->status === 'publicado')
                                    <a href="{{ route('blog.show', $artigo->slug) }}" target="_blank"
                                       class="text-brand hover:text-brand/70 text-xs no-underline" title="Ver no site">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    </a>
                                @endif
                                <a href="{{ route('blog.admin.form.edit', $artigo->id) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs no-underline" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('blog.admin.delete', $artigo->id) }}"
                                      onsubmit="return confirm('Excluir este artigo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs bg-transparent border-0 p-0 cursor-pointer" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Nenhum artigo encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($artigos->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
                {{ $artigos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
