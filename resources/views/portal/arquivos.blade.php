@extends('layouts.portal')

@section('title', 'Meus Arquivos')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Meus Arquivos</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Documentos e guias disponibilizados pela WR Assessoria para {{ $cliente->nome }}.</p>
    </div>

    @if (empty($arquivos))
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-10 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <p class="text-5xl mb-3">📂</p>
            <p class="font-medium">Nenhum arquivo disponível no momento.</p>
            <p class="text-xs mt-1">Em breve novos documentos serão adicionados aqui.</p>
        </div>
    @else
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#334155] border-b border-gray-200 dark:border-[#475569]">
                    <tr>
                        <th class="text-left px-5 py-3 text-gray-600 dark:text-slate-300 font-semibold">Arquivo</th>
                        <th class="text-left px-5 py-3 text-gray-600 dark:text-slate-300 font-semibold hidden sm:table-cell">Tamanho</th>
                        <th class="text-left px-5 py-3 text-gray-600 dark:text-slate-300 font-semibold hidden md:table-cell">Modificado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#334155]">
                    @foreach ($arquivos as $arquivo)
                    <tr class="hover:bg-gray-50 dark:hover:bg-[#334155]/50 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ match($arquivo['extensao']) {
                                    'pdf'  => '📄',
                                    'xls', 'xlsx' => '📊',
                                    'doc', 'docx' => '📝',
                                    'jpg', 'jpeg', 'png', 'gif' => '🖼️',
                                    'zip', 'rar', '7z' => '🗜️',
                                    default => '📎',
                                } }}</span>
                                <span class="font-medium text-gray-800 dark:text-slate-100">{{ $arquivo['nome'] }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-500 dark:text-slate-400 hidden sm:table-cell">{{ $arquivo['tamanho'] }}</td>
                        <td class="px-5 py-4 text-gray-500 dark:text-slate-400 hidden md:table-cell">{{ $arquivo['modificado'] }}</td>
                        <td class="px-5 py-4 text-right">
                            <a
                                href="{{ route('portal.arquivos.download', ['file' => $arquivo['nome']]) }}"
                                class="inline-flex items-center gap-1 text-[#0084AA] hover:text-[#006e8e] font-medium text-xs transition"
                            >
                                ⬇ Baixar
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
