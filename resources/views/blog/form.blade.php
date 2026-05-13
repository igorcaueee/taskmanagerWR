@extends('layouts.internal')

@section('title', ($artigo ? 'Editar Artigo' : 'Novo Artigo') . ' — WR Assessoria')

@push('styles')
<style>
    #conteudo { min-height: 300px; font-family: inherit; }
</style>
@endpush

@section('content')
<div class="py-6 px-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('blog.admin.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-brand no-underline text-sm">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
            {{ $artigo ? 'Editar Artigo' : 'Novo Artigo' }}
        </h1>
    </div>

    @if ($errors->any())
        <div class="mb-4 px-4 py-3 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Formulário principal --}}
        <div class="lg:col-span-2">
            <form method="POST"
                  action="{{ $artigo ? route('blog.admin.update', $artigo->id) : route('blog.admin.save') }}"
                  id="form-artigo">
                @csrf
                @if ($artigo) @method('PUT') @endif

                <div class="bg-white dark:bg-slate-800 rounded shadow p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Título *</label>
                        <input type="text" name="titulo" id="campo-titulo"
                               value="{{ old('titulo', $artigo?->titulo) }}" required maxlength="255"
                               class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Resumo (aparece na listagem)</label>
                        <textarea name="resumo" rows="2" maxlength="500"
                                  class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand resize-none">{{ old('resumo', $artigo?->resumo) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Conteúdo *</label>
                        <textarea name="conteudo" id="conteudo" rows="16" required
                                  class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand font-mono">{{ old('conteudo', $artigo?->conteudo) }}</textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Suporta HTML: &lt;h2&gt;, &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">URL da imagem de capa</label>
                        <input type="url" name="imagem_capa"
                               value="{{ old('imagem_capa', $artigo?->imagem_capa) }}" placeholder="https://..."
                               class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand">
                    </div>

                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status *</label>
                            <select name="status" id="campo-status"
                                    class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand">
                                <option value="rascunho"  @selected(old('status', $artigo?->status ?? 'rascunho') === 'rascunho')>Rascunho</option>
                                <option value="agendado"  @selected(old('status', $artigo?->status) === 'agendado')>Agendado</option>
                                <option value="publicado" @selected(old('status', $artigo?->status) === 'publicado')>Publicado</option>
                            </select>
                        </div>
                        <div class="flex-1" id="campo-publicado-em-wrapper">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Data/hora de publicação</label>
                            <input type="datetime-local" name="publicado_em"
                                   value="{{ old('publicado_em', $artigo?->publicado_em?->format('Y-m-d\TH:i')) }}"
                                   class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('blog.admin.index') }}"
                           class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-slate-600 rounded hover:bg-gray-100 dark:hover:bg-slate-700 no-underline">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-5 py-2 text-sm font-medium bg-brand text-white rounded border-0 hover:bg-brand/80 cursor-pointer">
                            <i class="fa-solid fa-floppy-disk"></i> {{ $artigo ? 'Salvar Alterações' : 'Criar Artigo' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Painel IA --}}
        <div class="lg:col-span-1 flex flex-col">
            <div class="bg-white dark:bg-slate-800 rounded shadow p-5 flex-1">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-purple-500"></i> Gerador com IA
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Título do artigo</label>
                        <input type="text" id="ia-titulo" maxlength="255"
                               placeholder="Ex: Como abrir um MEI em 2025"
                               class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2.5 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Tema / ângulo</label>
                        <textarea id="ia-tema" rows="6" maxlength="500"
                                  placeholder="Ex: Guia completo para quem quer se formalizar como MEI, explicando passo a passo, vantagens e obrigações"
                                  class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2.5 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Tom</label>
                        <select id="ia-tom"
                                class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2.5 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="educativo">Educativo</option>
                            <option value="formal">Formal</option>
                            <option value="informal">Informal</option>
                            <option value="persuasivo">Persuasivo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Palavras-chave (opcional)</label>
                        <input type="text" id="ia-keywords" maxlength="300"
                               placeholder="MEI, CNPJ, faturamento, 2025"
                               class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2.5 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>

                    <button type="button" id="btn-gerar"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white text-base font-semibold rounded border-0 cursor-pointer transition-colors">
                        <i class="fa-solid fa-bolt"></i> <span id="btn-gerar-label">Gerar Artigo</span>
                    </button>

                    <div id="ia-erro" class="hidden text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded p-3"></div>
                    <div id="ia-aviso" class="hidden text-sm text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900/20 rounded p-3">
                        <i class="fa-solid fa-check-circle"></i> Conteúdo inserido! Revise antes de salvar.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
(function () {
    const statusEl  = document.getElementById('campo-status');
    const wrapperEl = document.getElementById('campo-publicado-em-wrapper');

    function togglePublicadoEm() {
        wrapperEl.style.display = statusEl.value === 'agendado' ? '' : 'none';
    }
    togglePublicadoEm();
    statusEl.addEventListener('change', togglePublicadoEm);

    // Preenche título da IA com o título do formulário ao focar no painel
    document.getElementById('ia-titulo').addEventListener('focus', function () {
        if (!this.value) {
            const titulo = document.getElementById('campo-titulo').value;
            if (titulo) this.value = titulo;
        }
    });

    document.getElementById('btn-gerar').addEventListener('click', async function () {
        const titulo  = document.getElementById('ia-titulo').value.trim();
        const tema    = document.getElementById('ia-tema').value.trim();
        const tom     = document.getElementById('ia-tom').value;
        const keys    = document.getElementById('ia-keywords').value.trim();
        const erroEl  = document.getElementById('ia-erro');
        const avisoEl = document.getElementById('ia-aviso');
        const labelEl = document.getElementById('btn-gerar-label');

        erroEl.classList.add('hidden');
        avisoEl.classList.add('hidden');

        if (!titulo || !tema) {
            erroEl.textContent = 'Preencha o título e o tema.';
            erroEl.classList.remove('hidden');
            return;
        }

        this.disabled = true;
        labelEl.textContent = 'Gerando…';

        try {
            const res = await fetch('{{ route('blog.admin.gerar-ia') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ titulo, tema, tom, palavras_chave: keys }),
            });

            const json = await res.json();

            if (!res.ok) {
                erroEl.textContent = json.error ?? 'Erro ao gerar conteúdo.';
                erroEl.classList.remove('hidden');
                return;
            }

            document.getElementById('conteudo').value = json.conteudo;
            if (!document.getElementById('campo-titulo').value) {
                document.getElementById('campo-titulo').value = titulo;
            }
            avisoEl.classList.remove('hidden');
            document.getElementById('conteudo').scrollIntoView({ behavior: 'smooth' });
        } catch {
            erroEl.textContent = 'Erro de conexão. Tente novamente.';
            erroEl.classList.remove('hidden');
        } finally {
            this.disabled = false;
            labelEl.textContent = 'Gerar Artigo';
        }
    });
}());
</script>
@endpush
@endsection
