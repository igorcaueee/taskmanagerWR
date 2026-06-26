{{-- Partial: card de status Conta Azul — inclua no show do cliente --}}
@php
    $status = $cliente->statusContaAzul();
    $statusLabel = match($status) {
        'conectado'    => ['text' => 'Conectado', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', 'icon' => 'fa-circle-check'],
        'expirado'     => ['text' => 'Token expirado', 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', 'icon' => 'fa-triangle-exclamation'],
        default        => ['text' => 'Desconectado', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400', 'icon' => 'fa-circle-xmark'],
    };
@endphp

@if (auth()->user()?->canGerenciarFinanceiro())
<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4 mt-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="fa-solid fa-link text-blue-600 dark:text-blue-400 text-sm"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-slate-100">Conta Azul</p>
                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full {{ $statusLabel['class'] }}">
                    <i class="fa-solid {{ $statusLabel['icon'] }} text-xs"></i>
                    {{ $statusLabel['text'] }}
                </span>
                @if ($cliente->conta_azul_ultima_sincronizacao)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Última sync: {{ $cliente->conta_azul_ultima_sincronizacao->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
        </div>

        <div class="flex gap-2">
            @if ($status === 'desconectado' || $status === 'expirado')
                <a href="{{ route('conta-azul.redirect', $cliente) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 no-underline">
                    <i class="fa-solid fa-plug"></i>
                    {{ $status === 'expirado' ? 'Reconectar' : 'Conectar' }}
                </a>
            @else
                <button type="button"
                        onclick="sincronizarAgora({{ $cliente->id }})"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 border-0">
                    <i class="fa-solid fa-rotate"></i>
                    Sincronizar agora
                </button>
                <button type="button"
                        onclick="desconectarContaAzul({{ $cliente->id }})"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 text-red-600 dark:text-red-400 text-sm rounded border border-red-300 dark:border-red-600 hover:bg-red-50 dark:hover:bg-slate-600">
                    <i class="fa-solid fa-plug-circle-xmark"></i>
                    Desconectar
                </button>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
function sincronizarAgora(clienteId) {
    Swal.fire({
        icon: 'question',
        title: 'Sincronizar agora?',
        text: 'Um job será enviado para a fila e os dados serão atualizados em breve.',
        showCancelButton: true,
        confirmButtonText: 'Sim, sincronizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#16a34a',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(`/clientes/${clienteId}/conta-azul/sincronizar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(res => res.json())
        .then(data => Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, confirmButtonColor: '#2563eb' }))
        .catch(() => Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha ao iniciar sincronização.', confirmButtonColor: '#dc2626' }));
    });
}

function desconectarContaAzul(clienteId) {
    Swal.fire({
        icon: 'warning',
        title: 'Desconectar Conta Azul?',
        text: 'Os dados sincronizados permanecerão, mas novas sincronizações serão pausadas.',
        showCancelButton: true,
        confirmButtonText: 'Sim, desconectar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/clientes/${clienteId}/conta-azul/desconectar`;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    });
}

window.sincronizarAgora = sincronizarAgora;
window.desconectarContaAzul = desconectarContaAzul;
</script>
@endpush
@endif
