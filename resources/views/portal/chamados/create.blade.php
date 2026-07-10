@extends('layouts.portal')

@section('title', $tipo === 'admissao' ? 'Chamado de Admissão' : 'Chamado de Demissão')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <a href="{{ route('portal.chamados.index') }}" class="text-sm text-[#0084AA] no-underline hover:underline">&larr; Voltar para Chamados</a>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100 mt-2">
            {{ $tipo === 'admissao' ? 'Chamado de Admissão' : 'Chamado de Demissão' }}
        </h1>
        <p class="text-gray-500 dark:text-slate-400 mt-1 text-sm">
            Preencha os dados abaixo. Nossa equipe de DP será notificada assim que você enviar.
        </p>
    </div>

    <form method="POST" action="{{ route('portal.chamados.store') }}" enctype="multipart/form-data"
          class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm space-y-5">
        @csrf
        <input type="hidden" name="tipo" value="{{ $tipo }}">

        <div>
            <label for="nome_colaborador" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nome completo do colaborador *</label>
            <input type="text" name="nome_colaborador" id="nome_colaborador" value="{{ old('nome_colaborador') }}" required
                   class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="cpf" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">CPF</label>
                <input type="text" name="cpf" id="cpf" value="{{ old('cpf') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                <label for="cargo_funcao" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Cargo / Função</label>
                <input type="text" name="cargo_funcao" id="cargo_funcao" value="{{ old('cargo_funcao') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="data_evento" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">
                    {{ $tipo === 'admissao' ? 'Data prevista de admissão *' : 'Data do último dia trabalhado *' }}
                </label>
                <input type="date" name="data_evento" id="data_evento" value="{{ old('data_evento') }}" required
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                @if ($tipo === 'admissao')
                    <label for="motivo" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Tipo de contrato</label>
                    <select name="motivo" id="motivo"
                            class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
                        <option value="">Selecione...</option>
                        <option value="CLT" @selected(old('motivo') === 'CLT')>CLT</option>
                        <option value="Estágio" @selected(old('motivo') === 'Estágio')>Estágio</option>
                        <option value="Temporário" @selected(old('motivo') === 'Temporário')>Temporário</option>
                    </select>
                @else
                    <label for="motivo" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Motivo da demissão</label>
                    <select name="motivo" id="motivo"
                            class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
                        <option value="">Selecione...</option>
                        <option value="Pedido de demissão" @selected(old('motivo') === 'Pedido de demissão')>Pedido de demissão</option>
                        <option value="Sem justa causa" @selected(old('motivo') === 'Sem justa causa')>Sem justa causa</option>
                        <option value="Justa causa" @selected(old('motivo') === 'Justa causa')>Justa causa</option>
                        <option value="Término de contrato" @selected(old('motivo') === 'Término de contrato')>Término de contrato</option>
                        <option value="Acordo" @selected(old('motivo') === 'Acordo')>Acordo (mútuo)</option>
                    </select>
                @endif
            </div>
        </div>

        <div>
            <label for="observacoes" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Observações</label>
            <textarea name="observacoes" id="observacoes" rows="4"
                      class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">{{ old('observacoes') }}</textarea>
        </div>

        <div>
            <label for="arquivos" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Anexar documentos (opcional)</label>
            <input type="file" name="arquivos[]" id="arquivos" multiple
                   class="w-full text-sm text-gray-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#0084AA]/10 file:text-[#0084AA] file:text-sm file:font-medium hover:file:bg-[#0084AA]/20 bg-transparent">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('portal.chamados.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 no-underline transition">Cancelar</a>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-[#0084AA] text-white hover:bg-[#006d8a] transition border-0 cursor-pointer">Enviar chamado</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    @if ($errors->any())
        Swal.fire({ icon: 'error', title: 'Verifique os campos', text: @json($errors->first()) });
    @endif
</script>
@endpush
