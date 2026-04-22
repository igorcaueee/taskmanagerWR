@extends('layouts.internal')

@section('title', 'Explorador de Arquivos — WR Assessoria')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><i class="fa-regular fa-folder-open mr-2"></i>Meus Documentos</h1>
            {{-- Breadcrumbs --}}
            <nav class="flex items-center gap-1 text-sm text-gray-500 mt-1">
                <a href="{{ route('arquivos') }}" class="hover:text-blue-600 no-underline text-gray-600">
                    <i class="fa-solid fa-house-chimney"></i> Raiz
                </a>
                @foreach($breadcrumbs as $crumb)
                    <span class="text-gray-400">/</span>
                    <a href="{{ route('arquivos', ['path' => $crumb['path']]) }}" class="hover:text-blue-600 no-underline text-gray-600">{{ $crumb['name'] }}</a>
                @endforeach
            </nav>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                <input type="text" id="searchInput" placeholder="Buscar arquivos e pastas..." class="pl-8 pr-3 py-2 text-sm border border-gray-300 rounded bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56 transition">
            </div>
            <button onclick="document.getElementById('newFolderModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded border border-gray-300 transition">
                <i class="fa-solid fa-folder-plus"></i> Nova Pasta
            </button>
            <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload
            </button>
        </div>
    </div>

    {{-- File Table --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Nome</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Tamanho</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Modificado</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600 w-24">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @if(count($breadcrumbs) > 0)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3" colspan="4">
                            @php $parentPath = count($breadcrumbs) > 1 ? $breadcrumbs[count($breadcrumbs) - 2]['path'] : ''; @endphp
                            <a href="{{ route('arquivos', ['path' => $parentPath]) }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 no-underline">
                                <i class="fa-solid fa-arrow-turn-up fa-flip-horizontal text-gray-400"></i>
                                <span>..</span>
                            </a>
                        </td>
                    </tr>
                @endif

                @forelse($items as $item)
                    <tr class="hover:bg-gray-50 group" data-path="{{ $item['path'] }}" data-type="{{ $item['type'] }}" data-name="{{ $item['name'] }}">
                        <td class="px-4 py-3">
                            @if($item['type'] === 'folder')
                                <a href="{{ route('arquivos', ['path' => $item['path']]) }}" class="inline-flex items-center gap-2 text-gray-800 hover:text-blue-600 no-underline font-medium">
                                    <i class="fa-solid fa-folder text-yellow-500"></i>
                                    {{ $item['name'] }}
                                </a>
                            @else
                                <a href="{{ route('arquivos.download', ['path' => $item['path']]) }}" class="inline-flex items-center gap-2 text-gray-800 hover:text-blue-600 no-underline">
                                    <i class="fa-regular fa-file text-gray-400"></i>
                                    {{ $item['name'] }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">
                            @if($item['type'] === 'file')
                                {{ formatFileSize($item['size']) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 hidden md:table-cell">
                            {{ \Carbon\Carbon::createFromTimestamp($item['lastModified'])->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                @if($item['type'] === 'file')
                                    <a href="{{ route('arquivos.download', ['path' => $item['path']]) }}" class="p-1.5 text-gray-400 hover:text-blue-600 rounded hover:bg-blue-50" title="Baixar">
                                        <i class="fa-solid fa-download text-xs"></i>
                                    </a>
                                @endif
                                <button onclick="openRenameModal('{{ addslashes($item['path']) }}', '{{ addslashes($item['name']) }}')" class="p-1.5 text-gray-400 hover:text-amber-600 rounded hover:bg-amber-50 bg-transparent border-0" title="Renomear">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                <button onclick="confirmDelete('{{ addslashes($item['path']) }}', '{{ $item['type'] }}', '{{ addslashes($item['name']) }}')" class="p-1.5 text-gray-400 hover:text-red-600 rounded hover:bg-red-50 bg-transparent border-0" title="Excluir">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-gray-400">
                            <i class="fa-regular fa-folder-open text-4xl mb-2"></i>
                            <p class="mt-2">Esta pasta está vazia</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Upload Modal --}}
<div id="uploadModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold"><i class="fa-solid fa-cloud-arrow-up mr-2"></i>Enviar Arquivos</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 bg-transparent border-0">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="uploadForm" enctype="multipart/form-data">
            <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition cursor-pointer">
                <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                <p class="text-gray-600 text-sm">Arraste arquivos aqui ou clique para selecionar</p>
                <input type="file" id="fileInput" multiple class="hidden">
                <div id="fileList" class="mt-3 text-sm text-gray-700"></div>
            </div>
            <div id="uploadProgress" class="hidden mt-3">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                </div>
                <p id="progressText" class="text-xs text-gray-500 mt-1 text-center">Enviando...</p>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Enviar</button>
            </div>
        </form>
    </div>
</div>

{{-- New Folder Modal --}}
<div id="newFolderModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold"><i class="fa-solid fa-folder-plus mr-2"></i>Nova Pasta</h3>
            <button onclick="document.getElementById('newFolderModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 bg-transparent border-0">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="newFolderForm">
            <input type="text" id="folderName" name="name" placeholder="Nome da pasta" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('newFolderModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Criar</button>
            </div>
        </form>
    </div>
</div>

{{-- Rename Modal --}}
<div id="renameModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold"><i class="fa-solid fa-pen mr-2"></i>Renomear</h3>
            <button onclick="document.getElementById('renameModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 bg-transparent border-0">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="renameForm">
            <input type="hidden" id="renamePath">
            <input type="text" id="renameNewName" placeholder="Novo nome" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('renameModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Renomear</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const currentPath = @json($path);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ─── Upload ───
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    let selectedFiles = [];

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-blue-400', 'bg-blue-50'); });
    dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('border-blue-400', 'bg-blue-50'); });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        selectedFiles = Array.from(e.dataTransfer.files);
        showFileList();
    });
    fileInput.addEventListener('change', () => { selectedFiles = Array.from(fileInput.files); showFileList(); });

    function showFileList() {
        fileList.innerHTML = selectedFiles.map(f => `<div class="flex items-center gap-1"><i class="fa-regular fa-file text-gray-400"></i> ${f.name} <span class="text-gray-400">(${formatSize(f.size)})</span></div>`).join('');
    }

    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024, sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    document.getElementById('uploadForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (selectedFiles.length === 0) return;

        const formData = new FormData();
        formData.append('path', currentPath);
        selectedFiles.forEach(f => formData.append('files[]', f));

        const progress = document.getElementById('uploadProgress');
        const bar = document.getElementById('progressBar');
        progress.classList.remove('hidden');

        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 100);
                bar.style.width = pct + '%';
                document.getElementById('progressText').textContent = pct + '%';
            }
        });
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                window.location.reload();
            } else {
                alert('Erro ao enviar arquivos.');
                progress.classList.add('hidden');
            }
        });
        xhr.open('POST', '{{ route("arquivos.upload") }}');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.send(formData);
    });

    // ─── New Folder ───
    document.getElementById('newFolderForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('folderName').value.trim();
        if (!name) return;

        const res = await fetch('{{ route("arquivos.createFolder") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ path: currentPath, name })
        });

        if (res.ok) {
            window.location.reload();
        } else {
            const data = await res.json();
            alert(data.error || 'Erro ao criar pasta.');
        }
    });

    // ─── Rename ───
    function openRenameModal(path, name) {
        document.getElementById('renamePath').value = path;
        document.getElementById('renameNewName').value = name;
        document.getElementById('renameModal').classList.remove('hidden');
        document.getElementById('renameNewName').select();
    }

    document.getElementById('renameForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const path = document.getElementById('renamePath').value;
        const newName = document.getElementById('renameNewName').value.trim();
        if (!newName) return;

        const res = await fetch('{{ route("arquivos.rename") }}', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ path, newName })
        });

        if (res.ok) {
            window.location.reload();
        } else {
            const data = await res.json();
            alert(data.error || 'Erro ao renomear.');
        }
    });

    // ─── Search Filter ───
    document.getElementById('searchInput').addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('tbody tr[data-name]');

        rows.forEach(row => {
            const name = row.getAttribute('data-name').toLowerCase();
            row.style.display = (!query || name.includes(query)) ? '' : 'none';
        });

        const empty = document.getElementById('emptySearchMessage');
        if (empty) { empty.remove(); }

        const visible = Array.from(rows).filter(r => r.style.display !== 'none');
        if (query && visible.length === 0) {
            const tbody = document.querySelector('tbody');
            const tr = document.createElement('tr');
            tr.id = 'emptySearchMessage';
            tr.innerHTML = `<td colspan="4" class="px-4 py-8 text-center text-gray-400"><i class="fa-solid fa-magnifying-glass text-2xl mb-2"></i><p class="mt-2">Nenhum resultado para "<strong>${this.value.trim()}</strong>"</p></td>`;
            tbody.appendChild(tr);
        }
    });

    // ─── Delete ───
    function confirmDelete(path, type, name) {
        const label = type === 'folder' ? 'a pasta' : 'o arquivo';
        if (!confirm(`Tem certeza que deseja excluir ${label} "${name}"?`)) return;

        fetch('{{ route("arquivos.delete") }}', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ path, type })
        }).then(res => {
            if (res.ok) {
                window.location.reload();
            } else {
                res.json().then(data => alert(data.error || 'Erro ao excluir.'));
            }
        });
    }
</script>
@endpush
