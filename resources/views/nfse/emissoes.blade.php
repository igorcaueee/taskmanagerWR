@extends('layouts.internal')

@section('title', 'NFS-e Emitidas — ' . $cliente->nome)

@section('content')
<div class="w-full mx-auto py-6 px-4 max-w-5xl">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('nfse.emitir.form', $cliente) }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2 mt-1">
                <i class="fa-solid fa-file-invoice-dollar text-[#0084aa]"></i>
                NFS-e Emitidas — {{ $cliente->nome }}
            </h1>
        </div>
        <a href="{{ route('nfse.emitir.form', $cliente) }}"
           class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2 no-underline">
            <i class="fa-solid fa-plus"></i> Nova emissão
        </a>
    </div>

    @include('nfse._tabs')

    {{-- Trocar de empresa sem voltar pra aba Consultar --}}
    <div class="mb-6">
        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Empresa</label>
        <select id="selectEmpresaEmissoes"
                class="w-full max-w-md rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
            @foreach($clientes as $cli)
                <option value="{{ $cli->id }}" @selected($cli->id === $cliente->id)>{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Série/Número</th>
                    <th class="text-left px-4 py-3">Tomador</th>
                    <th class="text-right px-4 py-3">Valor</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Chave de Acesso</th>
                    <th class="text-right px-4 py-3">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($emissoes as $emissao)
                    <tr class="text-gray-800 dark:text-slate-200">
                        <td class="px-4 py-3">{{ $emissao->serie }}/{{ $emissao->numero }}</td>
                        <td class="px-4 py-3">{{ $emissao->tomador_nome }}</td>
                        <td class="px-4 py-3 text-right">R$ {{ number_format($emissao->valor_servico, 2, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @php
                                $cores = [
                                    'autorizada' => 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20',
                                    'rejeitada' => 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20',
                                    'cancelada' => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-700',
                                    'substituida' => 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20',
                                    'rascunho' => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-700',
                                    'enviada' => 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20',
                                ];
                            @endphp
                            <span class="text-xs px-2 py-1 rounded-full {{ $cores[$emissao->status] ?? '' }}">{{ ucfirst($emissao->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $emissao->chave_acesso ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($emissao->status === 'autorizada')
                                <button type="button" class="btnCancelar text-xs text-red-600 hover:underline bg-transparent border-0 p-0" data-id="{{ $emissao->id }}">
                                    Cancelar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-slate-500">Nenhuma NFS-e emitida ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $emissoes->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    document.getElementById('selectEmpresaEmissoes').addEventListener('change', function () {
        localStorage.setItem('nfseClienteAtual', this.value);
        window.location.href = `/nfse/emissoes/${this.value}`;
    });

    document.querySelectorAll('.btnCancelar').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;

            const { value: motivo } = await Swal.fire({
                title: 'Cancelar NFS-e',
                input: 'text',
                inputLabel: 'Motivo do cancelamento',
                showCancelButton: true,
                confirmButtonText: 'Cancelar nota',
                cancelButtonText: 'Voltar',
            });

            if (!motivo) return;

            try {
                const resp = await fetch(`/nfse/emissoes/${id}/cancelar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ motivo }),
                });
                const data = await resp.json();

                if (!resp.ok) {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao cancelar.' });
                    return;
                }

                Swal.fire({ icon: 'success', title: 'Cancelada!', timer: 1500, showConfirmButton: false })
                    .then(() => window.location.reload());
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na comunicação com o servidor.' });
            }
        });
    });
});
</script>
@endsection
