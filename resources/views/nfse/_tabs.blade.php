{{-- Barra de abas compartilhada entre as telas de NFS-e (Consultar / Emitir / Notas emitidas). --}}
<div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-slate-700 mb-6">
    <a href="{{ route('nfse.index') }}"
       class="nfse-tab-link px-4 py-2 text-sm font-medium border-b-2 no-underline {{ request()->routeIs('nfse.index') ? 'border-brand text-brand' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-brand' }}">
        <i class="fa-solid fa-magnifying-glass"></i> Consultar
    </a>
    <a href="#" id="nfseTabEmitir"
       class="nfse-tab-link px-4 py-2 text-sm font-medium border-b-2 no-underline {{ request()->routeIs('nfse.emitir.form') ? 'border-brand text-brand' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-brand' }}">
        <i class="fa-solid fa-file-circle-plus"></i> Emitir
    </a>
    <a href="#" id="nfseTabEmissoes"
       class="nfse-tab-link px-4 py-2 text-sm font-medium border-b-2 no-underline {{ request()->routeIs('nfse.emissoes') ? 'border-brand text-brand' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-brand' }}">
        <i class="fa-solid fa-file-invoice-dollar"></i> Notas emitidas
    </a>
</div>

<script>
(function () {
    // Mantém a última empresa selecionada em qualquer tela de NFS-e disponível
    // para as abas "Emitir" e "Notas emitidas", que são específicas de um cliente.
    const clienteAtual = {{ isset($cliente) ? $cliente->id : 'null' }};
    if (clienteAtual) {
        localStorage.setItem('nfseClienteAtual', clienteAtual);
    }

    function irParaAbaCliente(destino) {
        const id = localStorage.getItem('nfseClienteAtual');
        if (!id) {
            Swal.fire({
                icon: 'info',
                title: 'Selecione uma empresa',
                text: 'Escolha uma empresa na aba "Consultar" antes de acessar esta seção.',
            });
            return;
        }
        window.location.href = `/nfse/${destino}/${id}`;
    }

    document.getElementById('nfseTabEmitir')?.addEventListener('click', function (e) {
        e.preventDefault();
        irParaAbaCliente('emitir');
    });

    document.getElementById('nfseTabEmissoes')?.addEventListener('click', function (e) {
        e.preventDefault();
        irParaAbaCliente('emissoes');
    });
})();
</script>
