@extends('layouts.internal')

@section('title', 'Instruções da LIRI — WR Assessoria')

@section('content')
<div class="py-6 px-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-brain"></i> Instruções da LIRI</h1>
            <p class="text-gray-700 dark:text-gray-300">Conteúdo usado pela LIRI para responder com o conhecimento do escritório.</p>
        </div>
        <a href="{{ route('liri.instrucoes.form.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 no-underline text-sm font-medium">
            <i class="fa-solid fa-plus"></i> Nova Instrução
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 px-4 py-3 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 px-4 py-3 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700 text-gray-600 dark:text-gray-300 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Título</th>
                    <th class="px-4 py-3 text-left">Origem</th>
                    <th class="px-4 py-3 text-left">Autor</th>
                    <th class="px-4 py-3 text-left">Criado em</th>
                    <th class="px-4 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse ($instrucoes as $instrucao)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40">
                        <td class="px-4 py-3 text-gray-900 dark:text-slate-100 font-medium max-w-sm truncate">
                            {{ $instrucao->titulo }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($instrucao->arquivo_nome)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300" title="{{ $instrucao->arquivo_nome }}">
                                    <i class="fa-solid fa-file-arrow-up"></i> Arquivo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-600 text-gray-600 dark:text-gray-300">
                                    <i class="fa-solid fa-pen"></i> Texto
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $instrucao->autor?->nome ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                            {{ $instrucao->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('liri.instrucoes.form.edit', $instrucao->id) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs no-underline" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('liri.instrucoes.destroy', $instrucao->id) }}"
                                      data-confirm="Excluir esta instrução?" data-confirm-danger="1" data-confirm-ok="Excluir">
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
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Nenhuma instrução cadastrada ainda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
