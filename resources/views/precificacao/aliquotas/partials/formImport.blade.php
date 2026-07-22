<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-gray-900">
        <i class="fa-solid fa-file-import mr-1"></i> Importar Alíquotas
    </h2>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0 focus:outline-none">
        <i class="fa-solid fa-xmark text-xl"></i>
    </button>
</div>

<div class="mb-5 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
    <p class="font-medium mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Instruções</p>
    <ul class="list-disc list-inside space-y-0.5 text-blue-700">
        <li>Faça o download do modelo abaixo e preencha com os dados do relatório fiscal (ex: export do Econet Validador).</li>
        <li>As colunas <strong>ncm</strong> e <strong>aliquota_icms_interna</strong> são obrigatórias.</li>
        <li>Um mesmo NCM+CEST+UF já cadastrado será atualizado.</li>
        <li><strong>aplica_st</strong>: Sim ou Não (referência — o ICMS de cada compra é escolhido manualmente no cenário).</li>
        <li><strong>regime_pis_cofins</strong>: "Tributado" ou "Monofasico".</li>
    </ul>
</div>

<div class="mb-4">
    <a href="{{ route('precificacao.aliquotas.import.template') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded text-sm hover:bg-gray-50 focus:outline-none">
        <i class="fa-solid fa-download text-green-600"></i>
        Baixar modelo (.xlsx)
    </a>
</div>

<form method="POST" action="{{ route('precificacao.aliquotas.import') }}" enctype="multipart/form-data">
    @csrf

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Arquivo Excel (.xlsx)</label>
        <input type="file" name="arquivo" accept=".xlsx,.xls"
               class="block w-full text-sm text-gray-600 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-brand file:text-white hover:file:bg-brand/80">
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeModal()"
                class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none">
            Cancelar
        </button>
        <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white text-sm rounded border-0 hover:bg-brand/80 focus:outline-none">
            <i class="fa-solid fa-upload"></i>
            Importar
        </button>
    </div>
</form>
